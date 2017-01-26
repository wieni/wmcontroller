<?php

namespace Drupal\wmcontroller\Event;

use Drupal\wmcontroller\Entity\Cache;

use Symfony\Component\EventDispatcher\Event;

class CachePurgeEvent extends Event
{
    /** @var Cache */
    protected $cache;

    protected $expired;

    public function __construct(Cache $cache, $wasExpired = false)
    {
        $this->cache = $cache;
        $this->expired = $wasExpired;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Indicates whether this item simply expired or was purged,
     *
     * @return bool
     */
    public function wasExpired()
    {
        return $this->expired;
    }
}

