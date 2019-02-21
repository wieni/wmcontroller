<?php

namespace Drupal\wmcontroller\Commands;

use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;
use Drush\Commands\DrushCommands;

class CacheClearCommands extends DrushCommands
{
    /** @var \Drupal\wmcontroller\Service\Cache\Storage\StorageInterface */
    protected $storage;

    public function __construct(StorageInterface $storage) {
        $this->storage = $storage;
    }

    /**
     * Adds a cache clear option for wmcontroller.
     *
     * @hook on-event cache-clear
     */
    public function cacheClear(&$types, $include_bootstrapped_types)
    {
        if (!$include_bootstrapped_types) {
            return;
        }

        $types['wmcontroller'] = [$this->storage, 'flush'];
    }
}
