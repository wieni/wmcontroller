<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Entity\HasPresenterInterface;
use Drupal\wmcontroller\Entity\PresenterInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PresenterFactory implements PresenterFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;
    /** @var Dispatcher */
    protected $dispatcher;

    public function __construct(
        ContainerInterface $container,
        Dispatcher $dispatcher
    ) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
    }

    public function getPresenterForEntity(HasPresenterInterface $entity): PresenterInterface
    {
        $presenter = $this->container->get($entity->getPresenterService());
        $presenter->setEntity($entity);

        if ($entity instanceof EntityInterface) {
            $this->dispatcher->dispatchPresented($entity);
        }

        return $presenter;
    }
}
