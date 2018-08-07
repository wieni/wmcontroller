<?php

namespace Drupal\wmcontroller;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class WmcontrollerServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container)
    {
        if ($container->getParameter('wmcontroller.cache.tags')) {
            $container->removeDefinition('http_middleware.page_cache');
        }

        $container->setParameter(
            'twig.config',
            $container->getParameter('twig.config') +
            [
                'base_template_class' => '\\Drupal\\wmcontroller\\Twig\\Template',
            ]
        );

        if ($this->cacheBasedOnUserInfo($container)) {
            $this->runCacheMiddlewareAfterSessionMiddleware($container);
        }
    }

    protected function cacheBasedOnUserInfo(ContainerBuilder $container)
    {
        $flagName = 'wmcontroller.cache.ignore_authenticated_users';
        return $container->getParameter($flagName) === false;
    }

    protected function runCacheMiddlewareAfterSessionMiddleware(ContainerBuilder $container)
    {
        $middleware = 'wmcontroller.cache.middleware';
        $tags = $container->getDefinition($middleware)->getTags();
        $tags['http_middleware'][0]['priority'] = 49;
        $container->getDefinition($middleware)->setTags($tags);
    }
}

