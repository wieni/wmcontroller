<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmcontroller\Service\PresenterFactory;
use Drupal\wmcontroller\Entity\HasPresenterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PresenterSubscriber implements EventSubscriberInterface
{
    /** @var PresenterFactory */
    protected $factory;

    public function __construct(PresenterFactory $factory)
    {
        $this->factory = $factory;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::ENTITY_PRESENTED][] = [
            'onEntityPresented',
            0,
        ];

        return $events;
    }

    public function onEntityPresented(EntityPresentedEvent $event)
    {
        $e = $event->getEntity();
        if (!$e instanceof HasPresenterInterface) {
            return;
        }

        $event->setEntity($this->factory->getPresenterForEntity($e));
    }
}
