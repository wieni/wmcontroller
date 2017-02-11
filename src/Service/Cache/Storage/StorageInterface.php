<?php

namespace Drupal\wmcontroller\Service\Cache\Storage;

use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;

interface StorageInterface {

    /**
     * @return Cache
     *
     * @throws NoSuchCacheEntryException;
     */
    public function get($uri, $method = 'GET');

    /**
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[]
     */
    public function getByTag($tag);

    public function set(Cache $cache, array $tags);

    /**
     * Purge expired items, limited by $amount.
     *
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[] The purged cache entries.
     */
    public function purge($amount);

    /**
     * Purge items tagged with $tag.
     *
     * Note: Content nor headers will be hydrated.
     *
     * @return Cache[] The purged cache entries.
     */
    public function purgeByTag($tag);

    /**
     * Remove all cached entries.
     */
    public function flush();
}
