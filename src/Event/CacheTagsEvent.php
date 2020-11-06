<?php

namespace Drupal\wmcontroller\Event;

use Symfony\Component\EventDispatcher\Event;

class CacheTagsEvent extends Event
{
    /** @var string[] */
    protected $tags;

    public function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    public function getCacheTags(): array
    {
        return $this->tags;
    }
}
