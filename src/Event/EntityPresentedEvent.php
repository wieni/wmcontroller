<?php

namespace Drupal\wmcontroller\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

class EntityPresentedEvent extends Event
{
    /** @var EntityInterface */
    protected $entity;

    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return EntityInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return string[]
     */
    public function getCacheTags()
    {
        return $this->entity->getCacheTagsToInvalidate();
    }
}

