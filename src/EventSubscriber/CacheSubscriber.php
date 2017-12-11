<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Http\CachedResponse;
use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\Event\CacheTagsEvent;
use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CacheSubscriber implements EventSubscriberInterface
{
    const CACHE_HEADER = 'X-Wm-Cache';

    /** @var StorageInterface */
    protected $storage;

    /** @var AccountProxyInterface */
    protected $account;

    protected $expiries;

    protected $store;

    protected $tags;

    protected $addHeader;

    protected $presentedEntityTags = [];

    protected $ignoreAuthenticatedUsers;
    protected $ignoredRoles;

    /** @var EntityInterface */
    protected $mainEntity;

    protected $explicitMaxAges;

    protected $cacheableStatusCodes = [
        Response::HTTP_OK => true,
        Response::HTTP_NON_AUTHORITATIVE_INFORMATION => true,
    ];

    protected $ignores = [];

    public function __construct(
        StorageInterface $storage,
        AccountProxyInterface $account,
        array $expiries,
        $store = false,
        $tags = false,
        $addHeader = false,
        $ignoreAuthenticatedUsers = true,
        array $ignoredRoles = []
    ) {
        $this->storage = $storage;
        $this->account = $account;
        $this->expiries = $expiries + ['paths' => [], 'entities' => []];
        $this->store = $store;
        $this->tags = $tags;
        $this->addHeader = $addHeader;
        $this->ignoreAuthenticatedUsers = $ignoreAuthenticatedUsers;
        $this->ignoredRoles = $ignoredRoles;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::CACHE_HANDLE][] = ['onCachedResponse', 10000];
        $events[KernelEvents::RESPONSE][] = ['onResponse', -255];
        $events[KernelEvents::RESPONSE][] = ['onResponseEarly', 255];
        $events[KernelEvents::TERMINATE][] = ['onTerminate', 0];
        $events[WmcontrollerEvents::ENTITY_PRESENTED][] = ['onEntityPresented', 0];
        $events[WmcontrollerEvents::CACHE_TAGS][] = ['onTags', 0];
        $events[WmcontrollerEvents::MAIN_ENTITY_RENDER][] = ['onMainEntity', 0];

        return $events;
    }

    public function onMainEntity(MainEntityEvent $event)
    {
        $this->mainEntity = $event->getEntity();
    }

    public function onCachedResponse(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($this->ignore($request)) {
            return;
        }

        if (!$this->store || !$this->tags) {
            return;
        }

        try {
            $response = $this->getCache($request)->toResponse();
            // Check if we should respond with a 304
            // Not relevant atm with cache-control: max-age
            $response->isNotModified($request);

            if ($this->addHeader) {
                $response->headers->set(self::CACHE_HEADER, 'HIT');
            }
            $event->setResponse($response);
        } catch (NoSuchCacheEntryException $e) {
        }
    }

    public function onResponseEarly(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        if (
            $response->headers->hasCacheControlDirective('maxage')
            || $response->headers->hasCacheControlDirective('s-maxage')
        ) {
            $this->explicitMaxAges = [
                'maxage' => $response->headers
                    ->getCacheControlDirective('maxage'),
                's-maxage' => $response->headers
                    ->getCacheControlDirective('s-maxage'),
            ];
        }
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($this->ignore($request, true)) {
            return;
        }

        $response = $event->getResponse();
        if (!($response instanceof CachedResponse) && $this->addHeader) {
            $response->headers->set(self::CACHE_HEADER, 'MISS');
        }

        // Don't override explicitly set maxage headers.
        if (
            $response->headers->hasCacheControlDirective('maxage')
            || $response->headers->hasCacheControlDirective('s-maxage')
        ) {
            return;
        }

        if (!isset($this->cacheableStatusCodes[$response->getStatusCode()])) {
            return;
        }

        if (!empty($this->explicitMaxAges)) {
            $this->setMaxAge($response, $this->explicitMaxAges);
            return;
        }

        if ($entityExpiry = $this->getMaxAgesForMainEntity()) {
            $this->setMaxAge($response, $entityExpiry);
            return;
        }

        $smax = $request->attributes->get('_smaxage', 0);
        $max = $request->attributes->get('_maxage', 0);
        if ($smax || $max) {
            $this->setMaxAge(
                $response,
                ['s-maxage' => $smax, 'maxage' => $max]
            );
            return;
        }

        $path = $request->getPathInfo();
        foreach ($this->expiries['paths'] as $re => $definition) {
            // # should be safe... I guess
            if (!preg_match('#' . $re . '#', $path)) {
                continue;
            }

            $this->setMaxAge($response, $definition);

            return;
        }
    }

    public function onEntityPresented(EntityPresentedEvent $event)
    {
        foreach ($event->getCacheTags() as $tag) {
            $this->presentedEntityTags[$tag] = true;
        }
    }

    public function onTags(CacheTagsEvent $event)
    {
        foreach ($event->getCacheTags() as $tag) {
            $this->presentedEntityTags[$tag] = true;
        }
    }

    public function onTerminate(PostResponseEvent $event)
    {
        if (!$this->tags) {
            return;
        }

        $request = $event->getRequest();
        if ($this->ignore($request, true)) {
            return;
        }

        $response = $event->getResponse();

        if (
            $response instanceof CachedResponse
            || !$response->isCacheable()
        ) {
            return;
        }

        $body = '';
        $headers = [];
        if ($this->store) {
            $body = $response->getContent();
            $headers = $response->headers->all();
        }

        // fix drupal's retarded headers->set(..., ..., false) calls.
        $toUnset = [
            'x-content-type-options',
            'x-frame-options',
            'x-ua-compatible',
        ];

        foreach ($toUnset as $k) {
            unset($headers[$k]);
        }

        // TODO find a way to handle exceptions thrown from this point on.
        // (we're in the kernel::TERMINATE phase)
        $this->storage->set(
            new Cache(
                $this->getRequestUri($request),
                $request->getMethod(),
                $body,
                $headers,
                time() + $response->getMaxAge() // returns s-maxage if set.
            ),
            array_keys($this->presentedEntityTags)
        );
    }

    protected function ignore(Request $request, $setting = false)
    {
        $uri = $this->getRequestUri($request);
        if (isset($this->ignores[$uri][$setting])) {
            return $this->ignores[$uri][$setting];
        }

        if ($this->ignoreAuthenticatedUsers) {
            return $this->ignores[$uri][$setting] = $request->hasSession()
                && $request->getSession()->get('uid') != 0;
        }

        if ($setting) {
            if ((int) $this->account->id() === 1) {
                return $this->ignores[$uri][$setting] = true;
            }

            $account = $this->account->getAccount();
            $has = array_intersect($this->ignoredRoles, $account->getRoles());
            if (!empty($has)) {
                return $this->ignores[$uri][$setting] = true;
            }
        }

        return $this->ignores[$uri][$setting] = false;
    }

    /**
     * @return Cache The cached entry.
     *
     * @throws NoSuchCacheEntryException.
     */
    protected function getCache(Request $request)
    {
        return $this->storage->get(
            $this->getRequestUri($request),
            $request->getMethod()
        );
    }

    protected function getRequestUri(Request $request)
    {
        return $request->getSchemeAndHttpHost() .
            $request->getBaseUrl() .
            $request->getPathInfo();
    }

    protected function getMaxAgesForMainEntity()
    {
        if (!isset($this->mainEntity)) {
            return null;
        }

        $type = $this->mainEntity->getEntityTypeId();
        if (!isset($this->expiries['entities'][$type])) {
            return null;
        }

        $bundleDefs = $this->expiries['entities'][$type];

        $bundle = $this->mainEntity->bundle();

        if (isset($bundleDefs['_default'])) {
            $bundleDefs += [$bundle => $bundleDefs['_default']];
        }

        return $bundleDefs[$bundle];
    }

    protected function setMaxAge(Response $response, array $definition)
    {
        if (empty($definition['maxage']) && empty($definition['s-maxage'])) {
            return;
        }

        // Reset cache-control
        // (probably contains a must-revalidate or no-cache header)
        $response->headers->set('Cache-Control', '');

        // This triggers a bug in the default PageCache middleware
        // and is not actually needed according to the http spec.
        // But since clients ought to ignore it if a maxage is set,
        // it's pretty useless.
        //
        // Can be fixed from WmcontrollerServiceProvider using
        // $container->removeDefinition('http_middleware.page_cache');

        if ($this->store && $this->tags) {
            $response->headers->remove('expires');
        }

        if (!empty($definition['maxage'])) {
            $response->setMaxAge($definition['maxage']);
        }

        if (!empty($definition['s-maxage'])) {
            $response->setSharedMaxAge($definition['s-maxage']);
        }
    }
}
