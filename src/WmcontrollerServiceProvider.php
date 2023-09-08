<?php

namespace Drupal\wmcontroller;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

class WmcontrollerServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container)
    {
        if ($container->getParameter('wmcontroller.cache.tags')) {
            $container->removeDefinition('http_middleware.page_cache');
        }
    }
}
