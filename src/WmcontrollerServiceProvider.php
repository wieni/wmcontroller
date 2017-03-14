<?php

namespace Drupal\wmcontroller;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class WmcontrollerServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container)
    {
        if (
            $container->getParameter('wmcontroller.cache.store')
            && $container->getParameter('wmcontroller.cache.tags')
        ) {
            $container->removeDefinition('http_middleware.page_cache');
        }

        $container->setParameter(
            'twig.config',
            $container->getParameter('twig.config') +
            [
                'base_template_class' => '\\Drupal\\wmcontroller\\Twig\\Template',
            ]
        );
    }
}

