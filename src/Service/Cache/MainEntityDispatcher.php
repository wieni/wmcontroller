<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tiny convenience wrapper around the symfony event dispatcher
 * to notify our CacheSubscriber of which entity's cache rules apply
 * to the current request.
 */
class MainEntityDispatcher
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(EntityInterface $entity)
    {
        $this->dispatcher->dispatch(
            WmcontrollerEvents::MAIN_ENTITY_RENDER,
            new MainEntityEvent($entity)
        );
    }
}

