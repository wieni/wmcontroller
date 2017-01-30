<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Http\CachedResponse;
use Drupal\wmcontroller\Service\Cache\Manager;
use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\Event\CachePurgeEvent;
use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\Event\CacheTagsEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CacheSubscriber implements EventSubscriberInterface
{
    const CACHE_HEADER = 'X-WM-Cache';

    /** @var Manager */
    protected $manager;

    protected $expiries;

    protected $store;

    protected $tags;

    protected $presentedEntityTags = [];

    /** @var EntityInterface */
    protected $mainEntity;

    public function __construct(
        Manager $manager,
        array $expiries,
        $store = false,
        $tags = false
    ) {
        $this->manager = $manager;
        $this->expiries = $expiries + ['paths' => [], 'entities' => []];
        $this->store = $store;
        $this->tags = $tags;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::CACHE_HANDLE][] = ['onCachedResponse', 10000];
        $events[KernelEvents::RESPONSE][] = ['onResponse', -255];
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
        if (!$this->store || !$this->tags) {
            return;
        }

        $request = $event->getRequest();
        if ($this->ignore($request)) {
            return;
        }

        try {
            $response = $this->getCache($request)->toResponse();
            // Check if we should respond with a 304
            // Not relevant atm with cache-control: max-age
            $response->isNotModified($request);

            $response->headers->set(self::CACHE_HEADER, 'HIT');
            $event->setResponse($response);
        } catch (NoSuchCacheEntryException $e) {
        }
    }

    public function onResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($this->ignore($request)) {
            return;
        }

        $response = $event->getResponse();
        if (!($response instanceof CachedResponse)) {
            $response->headers->set(self::CACHE_HEADER, 'MISS');
        }

        // Don't override explicitly set maxage headers.
        if (
            $response->headers->hasCacheControlDirective('s-maxage')
            || $response->headers->hasCacheControlDirective('maxage')
        ) {
            return;
        }

        if ($entityExpiry = $this->getMaxAgesForMainEntity()) {
            $this->setMaxAge($response, $entityExpiry);
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
        if ($this->ignore($request)) {
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
        $this->manager->set(
            new Cache(
                $request->getUri(),
                $request->getMethod(),
                $body,
                $headers,
                time() + $response->getMaxAge() // returns s-maxage if set.
            ),
            array_keys($this->presentedEntityTags)
        );
    }

    protected function ignore(Request $request)
    {
        return $request->hasSession()
            && $request->getSession()->get('uid') != 0;
    }

    /**
     * @return Cache The cached entry.
     *
     * @throws NoSuchCacheEntryException.
     */
    protected function getCache(Request $request)
    {
        return $this->manager->get(
            $request->getUri(),
            $request->getMethod()
        );
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

        // $response->headers->remove('expires');

        if (!empty($definition['maxage'])) {
            $response->setMaxAge($definition['maxage']);
        }

        if (!empty($definition['s-maxage'])) {
            $response->setSharedMaxAge($definition['s-maxage']);
        }
    }
}

