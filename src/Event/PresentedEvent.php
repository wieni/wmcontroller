<?php

namespace Drupal\wmcontroller\Event;

use Drupal\Component\EventDispatcher\Event;

class PresentedEvent extends Event
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    /** @return mixed */
    public function getItem()
    {
        return $this->item;
    }

    public function setItem($item): void
    {
        $this->item = $item;
    }
}
