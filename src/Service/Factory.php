<?php

namespace Drupal\wmcontroller\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Factory
{
    public static function create(
        ContainerInterface $ctr,
        $serviceName
    ) {
        return $ctr->get($serviceName);
    }
}