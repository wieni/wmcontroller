<?php

namespace Drupal\wmcontroller\Service;

use Drupal\wmcontroller\Entity\HasPresenterInterface;
use Drupal\wmcontroller\Entity\PresenterInterface;

interface PresenterFactoryInterface
{
    public function getPresenterForEntity(HasPresenterInterface $entity): PresenterInterface;
}
