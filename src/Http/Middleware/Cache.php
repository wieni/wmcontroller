<?php

namespace Drupal\wmcontroller\Http\Middleware;

use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Cache implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    protected $next;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        HttpKernelInterface $next,
        EventDispatcherInterface $dispatcher
    ) {
        $this->next = $next;
        $this->dispatcher = $dispatcher;
    }

    public function handle(
        Request $request,
        $type = self::MASTER_REQUEST,
        $catch = true
    ) {
        if ($type !== static::MASTER_REQUEST) {
            return $this->next->handle($request, $type, $catch);
        }

        $event = new RequestEvent($this, $request, $type);

        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::CACHE_HANDLE
        );

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        return $this->next->handle($request, $type, $catch);
    }
}
