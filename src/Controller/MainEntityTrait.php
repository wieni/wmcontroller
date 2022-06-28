<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Service\MainEntityInterface;

trait MainEntityTrait
{
    /** @var MainEntityInterface */
    protected $mainEntity;

    public function setEntity(EntityInterface $entity): self
    {
        if (!isset($this->mainEntity)) {
            $this->mainEntity = \Drupal::service('wmcontroller.main_entity');
        }

        $this->mainEntity->setEntity($entity);

        return $this;
    }
}
