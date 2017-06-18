<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Event\PresentedEvent;
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
        $events[WmcontrollerEvents::PRESENTED][] = [
            'onEntityPresented',
            0,
        ];

        return $events;
    }

    public function onEntityPresented(PresentedEvent $event)
    {
        $e = $event->getItem();
        if (!$e instanceof HasPresenterInterface) {
            return;
        }

        $event->setItem($this->factory->getPresenterForEntity($e));
    }
}
