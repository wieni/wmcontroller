<?php

namespace Drupal\wmcontroller;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class WmcontrollerServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container)
    {
        $container->setParameter(
            'twig.config',
            $container->getParameter('twig.config') +
            [
                'base_template_class' => '\\Drupal\\wmcontroller\\Twig\\Template',
            ]
        );
    }
}

