<?php

namespace Drupal\wmcontroller;

final class WmcontrollerEvents {
    /**
     * Will be triggered whenever a variable is injected into
     * a template.
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\PresentedEvent
     */
    const PRESENTED = 'item.presented';

    /**
     * Will be triggered whenever a Drupal EntityInterface is injected into
     * a template.
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\EntityPresentedEvent
     *
     * @see Dispatcher::dispatchPresented().
     */
    const ENTITY_PRESENTED = 'entity.presented';

    /**
     * This event can be used to attach additional tags to the current page.
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\CacheTagsEvent
     *
     * @see Dispatcher::dispatchTags().
     */
    const CACHE_TAGS = 'cache.tags';

    /**
     * Will be triggered when an EntityInterface is being passed
     * to a controller. (i.e.: the main entity that is supposed to be rendered)
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\MainEntityEvent
     *
     * @see Dispatcher::dispatchMainEntity().
     */
    const MAIN_ENTITY_RENDER = 'entity.main.render';

    /**
     * Will be triggered when a cache entry is removed and ought to be
     * purged from a e.g.: a cdn if applicable.
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\CachePurgeEvent
     */
    const CACHE_PURGE = 'cache.purge';

    /**
     * Will be triggered from the Cache http middleware when a request
     * is suited for a cached response.
     *
     * The event object is an instance of
     * Symfony\Component\HttpKernel\Event\GetResponseEvent
     *
     * If a response is set on the event object no further processing will occur
     * and the response is served.
     */
    const CACHE_HANDLE = 'cache.handle';
}
