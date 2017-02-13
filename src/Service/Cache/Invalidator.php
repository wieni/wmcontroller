<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

class Invalidator implements CacheTagsInvalidatorInterface
{
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function invalidateTags(array $tags)
    {
        foreach ($tags as $tag) {
            $this->manager->purgeByTag($tag);
        }
    }
}

