<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Http\CachedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CacheSubscriber implements EventSubscriberInterface
{
    protected $expiries;

    public function __construct(array $config)
    {
        $config += ['expiry' => []];
        $this->expiries = $config['expiry'];
    }

    public static function getSubscribedEvents()
    {
        $events[KernelEvents::REQUEST][] = ['onCachedResponse', 10000];
        $events[KernelEvents::RESPONSE][] = ['onResponse', -255];
        $events[KernelEvents::TERMINATE][] = ['onTerminate', 0];
        return $events;
    }

    public function onCachedResponse(GetResponseEvent $event)
    {
        if ($this->ignore()) {
            return;
        }

        try {
            $event->setResponse($this->getCache($event->getRequest())
                ->toResponse());
        } catch (NoSuchCacheEntryException $e) {
        }
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if ($this->ignore()) {
            return;
        }

        $response = $event->getResponse();
        if (
            empty($this->expiries)
            || $response->headers->hasCacheControlDirective('s-maxage')
        ) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        foreach ($this->expiries as $re => $expiry) {
            // # should be safe... I guess
            if (!preg_match('#' . $re . '#', $path)) {
                continue;
            }

            if ($expiry) {
                $response->setSharedMaxAge($expiry);
            }

            return;
        }
    }

    public function onTerminate(PostResponseEvent $event)
    {
        if ($this->ignore()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if (
            $response instanceof CachedResponse
            || !$response->isCacheable()
        ) {
            return;
        }

        $fn = $this->filepath($request);
        file_put_contents(
            $fn . '.content',
            $response->getContent()
        );

        file_put_contents(
            $fn . '.headers',
            serialize($response->headers->all())
        );
    }

    protected function ignore()
    {
        return \Drupal::service('current_user')->id() != 0;
    }

    protected function filepath(Request $request)
    {
        return '/tmp/' . sha1(
            $request->getMethod() . '|' . $request->getPathInfo()
        );
    }

    /**
     * @return Cache The cached entry.
     *
     * @throws NoSuchCacheEntryException.
     */
    protected function getCache(Request $request)
    {
        $fn = $this->filepath($request);
        $contentPath = $fn . '.content';
        if (!file_exists($contentPath)) {
            throw new NoSuchCacheEntryException(
                $request->getMethod(),
                $request->getPathInfo()
            );
        }

        $content = file_get_contents($contentPath);
        $headers = unserialize(
            file_get_contents($fn . '.headers')
        );

        return new Cache(
            '<p>heejkes, ik kom uit de cache. xoxo</p>' . $content,
            $headers
        );
    }
}

