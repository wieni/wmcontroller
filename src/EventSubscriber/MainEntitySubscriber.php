<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Event\MainEntityEvent;
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

    public static function getSubscribedEvents()
    {
        return [
            WmcontrollerEvents::MAIN_ENTITY_RENDER => ['onMainEntity'],
        ];
    }

    public function onMainEntity(MainEntityEvent $event)
    {
        $this->mainEntity->setEntity(
            $event->getEntity()
        );
    }
}
