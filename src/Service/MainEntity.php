<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;

class MainEntity implements MainEntityInterface
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
