<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;

class MainEntity
{
    /** @var EntityInterface */
    protected $entity;

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity(EntityInterface $entity)
    {
        $this->entity = $entity;
    }
}
