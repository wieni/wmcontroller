<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;

interface EntityControllerResolverInterface
{
    public function getController(EntityInterface $entity);
}
