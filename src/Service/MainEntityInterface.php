<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;

interface MainEntityInterface
{
    public function getEntity(): ?EntityInterface;

    public function setEntity(EntityInterface $entity): void;
}
