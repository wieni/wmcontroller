<?php

namespace Drupal\wmcontroller;

final class WmcontrollerEvents {
    /**
     * Will be triggered whenever a Drupal EntityInterface is injected into
     * a template.
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\EntityPresentedEvent
     */
    const ENTITY_PRESENTED = 'entity.presented';

    /**
     * Will be triggered when a cache entry is removed and ought to be
     * purged from a e.g.: a cdn if applicable.
     *
     * The event object is an instance of
     * Drupal\wmcontroller\Event\CachePurgeEvent
     */
    const CACHE_PURGE = 'cache.purge';
}
