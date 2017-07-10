<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\Event\CacheTagsEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tiny convenience wrapper around the symfony event dispatcher
 */
class Dispatcher
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return MainEntityEvent
     */
    public function dispatchMainEntity(EntityInterface $entity)
    {
        $event = new MainEntityEvent($entity);
        $this->dispatcher->dispatch(
            WmcontrollerEvents::MAIN_ENTITY_RENDER,
            $event
        );

        return $event;
    }

    /**
     * @return EntityPresentedEvent
     */
    public function dispatchPresented(EntityInterface $entity)
    {
        $event = new EntityPresentedEvent($entity);
        $this->dispatcher->dispatch(
            WmcontrollerEvents::ENTITY_PRESENTED,
            $event
        );

        return $event;
    }

    /**
     * @return CacheTagsEvent
     */
    public function dispatchTags(array $tags)
    {
        $event = new CacheTagsEvent($tags);
        $this->dispatcher->dispatch(
            WmcontrollerEvents::CACHE_TAGS,
            $event
        );

        return $event;
    }
}

