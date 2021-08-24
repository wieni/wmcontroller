<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Event\CacheInsertEvent;
use Drupal\wmcontroller\Event\CacheTagsEvent;
use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\Event\ValidationEvent;
use Drupal\wmcontroller\Service\Cache\Validation\CacheableRequestResult;
use Drupal\wmcontroller\Service\Cache\Validation\CacheableResponseResult;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /** @return MainEntityEvent */
    public function dispatchMainEntity(EntityInterface $entity)
    {
        $event = new MainEntityEvent($entity);
        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::MAIN_ENTITY_RENDER
        );

        return $event;
    }

    /** @return EntityPresentedEvent */
    public function dispatchPresented(EntityInterface $entity)
    {
        $event = new EntityPresentedEvent($entity);
        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::ENTITY_PRESENTED
        );

        return $event;
    }

    /** @return CacheTagsEvent */
    public function dispatchTags(array $tags)
    {
        $event = new CacheTagsEvent($tags);
        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::CACHE_TAGS
        );

        return $event;
    }

    /** @return CacheInsertEvent */
    public function dispatchCacheInsertEvent(
        Cache $cache,
        Request $request,
        Response $response,
        array $tags
    ) {
        $event = new CacheInsertEvent($cache, $tags, $request, $response);
        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::CACHE_INSERT
        );

        return $event;
    }

    /** @return ValidationEvent */
    public function dispatchRequestCacheablityValidation(Request $request)
    {
        $event = new ValidationEvent(
            $request,
            null,
            CacheableRequestResult::class
        );
        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::VALIDATE_CACHEABILITY_REQUEST
        );
        return $event;
    }

    /** @return ValidationEvent */
    public function dispatchResponseCacheablityValidation(Request $request, Response $response)
    {
        $event = new ValidationEvent(
            $request,
            $response,
            CacheableResponseResult::class
        );
        $this->dispatcher->dispatch(
            $event,
            WmcontrollerEvents::VALIDATE_CACHEABILITY_RESPONSE
        );
        return $event;
    }
}
