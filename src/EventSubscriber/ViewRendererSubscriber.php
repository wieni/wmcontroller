<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\ViewBuilder\ViewBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewRendererSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // Just make sure i'm run before the MainContentViewSubscriber
        $events[KernelEvents::VIEW][] = ['renderView', 99];

        return $events;
    }

    public function renderView(ViewEvent $event)
    {
        $result = $event->getControllerResult();
        if ($result instanceof ViewBuilder) {
            // Replace the controller result with a render-array
            $event->setControllerResult($result->toRenderArray());
        }
    }
}
