<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Entity\Cache;

interface CacheSerializerInterface
{
    /**
     * @param \Drupal\wmcontroller\Entity\Cache $cache
     * @param bool $includeContent
     *
     * @return mixed
     */
    public function normalize(Cache $cache, $includeContent = true);

    /**
     * @param mixed $row
     *
     * @return \Drupal\wmcontroller\Entity\Cache
     */
    public function denormalize($row);
}
