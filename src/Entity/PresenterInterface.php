<?php

namespace Drupal\wmcontroller\Entity;

use Drupal\Core\Entity\EntityInterface;

interface PresenterInterface extends EntityInterface
{
    public function setEntity(EntityInterface $entity);

    /**
     * @return EntityInterface
     */
    public function getEntity();
}
