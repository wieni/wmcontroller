<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;

class Invalidator implements InvalidatorInterface
{
    /** @var \Drupal\wmcontroller\Service\Cache\Storage\StorageInterface */
    protected $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function invalidateCacheTags(array $tags)
    {
        $this->storage->remove(
            $this->storage->getByTags($tags)
        );
    }
}

