<?php

namespace Drupal\wmcontroller\Http\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\Core\PageCache\RequestPolicyInterface;

class Cache implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    protected $next;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var RequestPolicyInterface */
    protected $policy;

    public function __construct(
        HttpKernelInterface $next,
        EventDispatcherInterface $dispatcher,
        RequestPolicyInterface $policy
    ) {
        $this->next = $next;
        $this->dispatcher = $dispatcher;
        $this->policy = $policy;
    }

    public function handle(
        Request $request,
        $type = self::MASTER_REQUEST,
        $catch = true
    ) {
        if (
            $type !== static::MASTER_REQUEST
            || $this->policy->check($request) !== RequestPolicyInterface::ALLOW
        ) {
            return $this->next->handle($request, $type, $catch);
        }

        $event = new GetResponseEvent($this, $request, $type);

        $this->dispatcher->dispatch(
            WmcontrollerEvents::CACHE_HANDLE,
            $event
        );

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        return $this->next->handle($request, $type, $catch);
    }
}

