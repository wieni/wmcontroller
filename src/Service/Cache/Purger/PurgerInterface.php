<?php

namespace Drupal\wmcontroller\Service\Cache\Purger;

use Drupal\wmcontroller\Entity\Cache;

interface PurgerInterface {

    /**
     * Purge the cache
     *
     * @param Cache[] $items
     * @return boolean
     */
    public function purge(array $items);

    /**
     * Purge everything
     * @return boolean
     */
    public function flush();
}