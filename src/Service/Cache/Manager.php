<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Event\CachePurgeEvent;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Manager implements StorageInterface
{
    /** @var StorageInterface */
    protected $storage;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        StorageInterface $storage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[]
     */
    public function getByTag($tag)
    {
        $results = $this->storage->getByTag($tag);
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

    /**
     * @return Cache
     *
     * @throws NoSuchCacheEntryException;
     */
    public function get($uri, $method = 'GET')
    {
        return $this->storage->get($uri, $method);
    }

    public function set(Cache $cache, array $tags)
    {
        return $this->storage->set($cache, $tags);
    }

    /**
     * Purge expired items, limited by $amount.
     *
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[] The purged cache entries.
     */
    public function purge($amount)
    {
        $results = $this->dispatch($this->storage->purge($amount), true);
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

    /**
     * Purge items tagged with $tag.
     *
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[] The purged cache entries.
     */
    public function purgeByTag($tag)
    {
        $results = $this->dispatch($this->storage->purgeByTag($tag));
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

    protected function dispatch(array $items, $expired = false)
    {
        foreach ($items as $item) {
            $this->dispatcher->dispatch(
                WmcontrollerEvents::CACHE_PURGE,
                new CachePurgeEvent($item, $expired)
            );
        }

        return $items;
    }
}

