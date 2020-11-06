<?php

namespace Drupal\wmcontroller\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

class MainEntityEvent extends Event
{
    /** @var EntityInterface */
    protected $entity;

    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }
}
