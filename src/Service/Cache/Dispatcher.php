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

    public function dispatchMainEntity(EntityInterface $entity)
    {
        $this->dispatcher->dispatch(
            WmcontrollerEvents::MAIN_ENTITY_RENDER,
            new MainEntityEvent($entity)
        );
    }

    public function dispatchPresented(EntityInterface $entity)
    {
        $this->dispatcher->dispatch(
            WmcontrollerEvents::ENTITY_PRESENTED,
            new EntityPresentedEvent($entity)
        );
    }

    public function dispatchTags(array $tags)
    {
        $this->dispatcher->dispatch(
            WmcontrollerEvents::CACHE_TAGS,
            new CacheTagsEvent($tags)
        );
    }
}

