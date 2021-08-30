<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmtwig\ViewBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewRendererSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // Just make sure i'm run before the MainContentViewSubscriber
        $events[KernelEvents::VIEW][] = ['renderView', 99];

        return $events;
    }

    public function renderView(GetResponseForControllerResultEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof ViewBuilder) {
            $event->setControllerResult($result->toRenderArray());
        }
    }
}
