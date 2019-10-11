<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;

interface MainEntityInterface
{
    public function getEntity();

    public function setEntity(EntityInterface $entity);
}
