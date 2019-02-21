<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Entity\Cache;

interface CacheSerializerInterface
{
    /**
     * @param \Drupal\wmcontroller\Entity\Cache $cache
     *
     * @return array
     */
    public function normalize(Cache $cache);

    /**
     * @param array $row
     *
     * @return \Drupal\wmcontroller\Entity\Cache
     */
    public function denormalize(array $row);
}
