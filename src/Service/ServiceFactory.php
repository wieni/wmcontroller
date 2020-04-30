<?php

namespace Drupal\wmcontroller\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Used to make services swappable using container parameters,
 * without having to alter service definitions using a service provider.
 */
class ServiceFactory implements ServiceFactoryInterface
{
    public static function create(ContainerInterface $container, string $serviceName)
    {
        return $container->get($serviceName);
    }
}
