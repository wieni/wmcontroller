<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Service\Cache\Purger\PurgerInterface;
use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;

class Manager
{
    /** @var StorageInterface */
    protected $storage;

    /** @var PurgerInterface */
    protected $purger;

    public function __construct(
        StorageInterface $storage,
        PurgerInterface $purger
    ) {
        $this->storage = $storage;
        $this->purger = $purger;
    }

    public function purgeByTags(array $tags)
    {
        $items = $this->storage->getByTags($tags);
        if ($this->purger->purge($items)) {
            $this->storage->remove($items);
        }
    }

    public function expireTags(array $tags)
    {
        $this->storage->expire($this->storage->getByTags($tags));
    }

    public function purge($amount)
    {
        $this->storage->remove(
            $this->storage->getExpired($amount)
        );
    }

    public function flush()
    {
        $this->purger->flush();
        $this->storage->flush();
    }
}
