<?php

namespace Drupal\wmcontroller\Service;

use Drupal\wmcontroller\Entity\HasPresenterInterface;

interface PresenterFactoryInterface
{
    public function getPresenterForEntity(HasPresenterInterface $entity);
}
