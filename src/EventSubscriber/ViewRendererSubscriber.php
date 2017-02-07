<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\ViewBuilder\ViewBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber ViewRendererSubscriber.
 */
class ViewRendererSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Just make sure i'm run before the MainContentViewSubscriber
        $events[KernelEvents::VIEW][] = ['renderView', 99];

        return $events;
    }

    public function renderView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();
        if ($result instanceof ViewBuilder) {
            // Replace the controller result with a render-array
            $event->setControllerResult($result->render());
        }
    }
}

