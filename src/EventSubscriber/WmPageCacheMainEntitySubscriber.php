<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Service\MainEntity;
use Drupal\wmpage_cache\Event\MainEntityAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WmPageCacheMainEntitySubscriber implements EventSubscriberInterface
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
        $events['wmpage_cache.maxage_alter'][] = ['onMainEntityAlter'];

        return $events;
    }

    public function onMainEntityAlter(MainEntityAlterEvent $event)
    {
        $event->setEntity(
            $this->mainEntity->getEntity()
        );
    }
}
