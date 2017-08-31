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
    )
    {
        $this->storage = $storage;
        $this->purger = $purger;
    }

    public function purge($amount)
    {
        $expiredItems = $this->storage->getExpired($amount);
        if ($this->purger->purge($expiredItems)) {
            $this->storage->remove($expiredItems);
        }
    }

    public function flush()
    {
        $this->purger->flush();
        $this->storage->flush();
    }
}