<?php

namespace Drupal\wmcontroller\Commands;

use Drupal\wmcontroller\Service\Cache\Manager;
use Drush\Commands\DrushCommands;

class CacheClearCommands extends DrushCommands
{
    /** @var Manager */
    protected $manager;

    public function __construct(
        Manager $manager
    ) {
        $this->manager = $manager;
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

        $types['wmcontroller'] = [$this->manager, 'flush'];
    }
}
