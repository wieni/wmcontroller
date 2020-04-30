<?php

namespace Drupal\wmcontroller\Service;

use Drupal\wmcontroller\Entity\HasPresenterInterface;
use Drupal\wmcontroller\Entity\PresenterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PresenterFactory implements PresenterFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPresenterForEntity(HasPresenterInterface $entity): PresenterInterface
    {
        $presenter = $this->container->get($entity->getPresenterService());

        $presenter->setEntity($entity);

        return $presenter;
    }
}
