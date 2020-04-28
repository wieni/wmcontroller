<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Event\CacheTagsEvent;
use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Http\CachedResponse;
use Drupal\wmcontroller\Service\Cache\EnrichRequest;
use Drupal\wmcontroller\Service\Cache\Manager;
use Drupal\wmcontroller\Service\Cache\MaxAgeInterface;
use Drupal\wmcontroller\Service\Cache\Validation\Validation;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CacheSubscriber implements EventSubscriberInterface
{
    public const CACHE_HEADER = 'X-Wm-Cache';

    /** @var Manager */
    protected $manager;

    /** @var \Drupal\wmcontroller\Service\Cache\Validation\Validation */
    protected $validation;

    /** @var \Drupal\wmcontroller\Service\Cache\EnrichRequest */
    protected $enrichRequest;

    /** @var MaxAgeInterface */
    protected $maxAgeStrategy;

    protected $addHeader;

    protected $strippedHeaders = [];

    protected $presentedEntityTags = [];

    public function __construct(
        Manager $manager,
        Validation $validation,
        EnrichRequest $enrichRequest,
        MaxAgeInterface $maxAgeStrategy,
        $addHeader = false,
        array $strippedHeaders
    ) {
        $this->manager = $manager;
        $this->validation = $validation;
        $this->maxAgeStrategy = $maxAgeStrategy;
        $this->addHeader = $addHeader;
        $this->enrichRequest = $enrichRequest;
        $this->strippedHeaders = $strippedHeaders;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::CACHE_HANDLE][] = ['onEnrichRequest', 10001];
        $events[WmcontrollerEvents::CACHE_HANDLE][] = ['onGetCachedResponse', 10000];
        $events[KernelEvents::RESPONSE][] = ['onResponse', -255];
        $events[KernelEvents::TERMINATE][] = ['onTerminate', 0];
        $events[WmcontrollerEvents::ENTITY_PRESENTED][] = ['onEntityPresented', 0];
        $events[WmcontrollerEvents::CACHE_TAGS][] = ['onTags', 0];

        return $events;
    }

    public function onEnrichRequest(GetResponseEvent $event)
    {
        // Do a faster-than-drupal user and session lookup
        // Fills the Request attribute with:
        // - '_wmcontroller.uid'
        // - '_wmcontroller.roles'
        // - '_wmcontroller.session'
        $this->enrichRequest->enrichRequest($event->getRequest());
    }

    public function onGetCachedResponse(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $check = $this->validation->shouldIgnoreRequest($request);
        if (!$check->allowCachedResponse()) {
            return;
        }

        try {
            $response = $this->manager->get($request)->toResponse();
            // Check if we should respond with a 304
            // Not relevant atm with cache-control: max-age
            $response->isNotModified($request);

            if ($this->addHeader) {
                $response->headers->set(self::CACHE_HEADER, 'HIT');
            }

            if (empty($response->getContent())) {
                return;
            }

            $event->setResponse($response);
        } catch (NoSuchCacheEntryException $e) {
        }
    }

    public function onResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (
            !$event->isMasterRequest()
            || $response instanceof CachedResponse
            || empty($response->getContent())
        ) {
            return;
        }

        foreach ($this->strippedHeaders as $remove) {
            $response->headers->remove($remove);
        }

        if ($this->addHeader) {
            $response->headers->set(self::CACHE_HEADER, 'MISS');
        }

        // Don't override explicitly set maxage headers.
        if (
            $response->headers->hasCacheControlDirective('max-age')
            || $response->headers->hasCacheControlDirective('s-maxage')
        ) {
            return;
        }

        $check = $this->validation->shouldIgnoreResponse($request, $response);
        if (!$check->allowCached()) {
            $this->setMaxAge(
                $response,
                [
                    'maxage' => 0,
                    's-maxage' => 0,
                    'wm-s-maxage' => null,
                ]
            );
            return;
        }

        $this->setMaxAge(
            $response,
            $this->maxAgeStrategy->getMaxage($request, $response)
        );
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
        if (
            !$event->isMasterRequest()
            || !$event->getResponse()->isCacheable()
        ) {
            return;
        }

        $this->manager->store(
            $event->getRequest(),
            $event->getResponse(),
            array_keys($this->presentedEntityTags)
        );
    }

    protected function setMaxAge(Response $response, array $definition)
    {
        if (
            !isset($definition['maxage'])
            && !isset($definition['s-maxage'])
        ) {
            return;
        }

        // Reset cache-control
        // (probably contains a must-revalidate or no-cache header)
        $response->headers->set('Cache-Control', '');

        if (empty($definition['maxage']) && empty($definition['s-maxage'])) {
            return;
        }

        // This triggers a bug in the default PageCache middleware
        // and is not actually needed according to the http spec.
        // But since clients ought to ignore it if a maxage is set,
        // it's pretty useless.
        //
        // Can be fixed from WmcontrollerServiceProvider using
        // $container->removeDefinition('http_middleware.page_cache');
        $response->headers->remove('expires');

        if (!empty($definition['maxage'])) {
            $response->setMaxAge($definition['maxage']);
        }

        if (isset($definition['s-maxage'])) {
            $response->setSharedMaxAge($definition['s-maxage']);
        }

        if (isset($definition['wm-s-maxage'])) {
            $response->headers->addCacheControlDirective(
                'wm-s-maxage',
                $definition['wm-s-maxage']
            );
        }
    }
}
