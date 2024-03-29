<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\Service\MainEntity;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MainEntitySubscriber implements EventSubscriberInterface
{
    /** @var MainEntity */
    protected $mainEntity;

    public function __construct(
        MainEntity $mainEntity
    ) {
        $this->mainEntity = $mainEntity;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WmcontrollerEvents::MAIN_ENTITY_RENDER => ['onMainEntity'],
        ];
    }

    public function onMainEntity(MainEntityEvent $event): void
    {
        $this->mainEntity->setEntity(
            $event->getEntity()
        );
    }
}
