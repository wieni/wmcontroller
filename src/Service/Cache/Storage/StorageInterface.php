<?php

namespace Drupal\wmcontroller\Service\Cache\Storage;

use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;

interface StorageInterface {

    /**
     * Get expired items, limited by $amount.
     *
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[] The expired cache entries.
     */
    public function getExpired($amount);

    /**
     * @return Cache
     *
     * @throws NoSuchCacheEntryException;
     */
    public function get($uri, $method = 'GET');

    public function set(Cache $item, array $tags);

    /**
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[]
     */
    public function getByTags(array $tags);

    /**
     * @param Cache[] $items
     */
    public function expire(array $items);

    /**
     * @param Cache[] $items
     */
    public function remove(array $items);

    /**
     * Remove all cache entries.
     */
    public function flush();
}
