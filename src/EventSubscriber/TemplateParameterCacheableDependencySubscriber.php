<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Event\PresentedEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TemplateParameterCacheableDependencySubscriber implements EventSubscriberInterface
{
    protected static $dispatched = [];

    public static function getSubscribedEvents(): array
    {
        $events[WmcontrollerEvents::PRESENTED][] = ['onTemplateParameter'];

        return $events;
    }

    public function onTemplateParameter(PresentedEvent $event): void
    {
        $value = $event->getItem();

        if (!($value instanceof \Drupal\Core\Entity\EntityInterface)) {
            return;
        }

        $key = sprintf('%s:%s', $value->getEntityTypeId(), $value->id());
        if (isset(self::$dispatched[$key])) {
            return;
        }

        self::$dispatched[$key] = true;
        \Drupal::service('wmcontroller.cache.dispatcher')
            ->dispatchPresented($value);
    }

}
