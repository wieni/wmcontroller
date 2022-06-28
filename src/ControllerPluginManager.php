<?php

namespace Drupal\wmcontroller;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wmcontroller\Annotation\Controller;

class ControllerPluginManager extends DefaultPluginManager
{
    public function __construct(
        \Traversable $namespaces,
        CacheBackendInterface $cacheBackend,
        ModuleHandlerInterface $moduleHandler
    ) {
        parent::__construct(
            'Controller',
            $namespaces,
            $moduleHandler,
            null,
            Controller::class
        );
        $this->alterInfo('wmcontroller_controller_info');
        $this->setCacheBackend($cacheBackend, 'wmcontroller_controller_info');
    }

    protected function getFactory()
    {
        if (!$this->factory) {
            $this->factory = new ControllerPluginFactory($this, $this->pluginInterface);
        }

        return $this->factory;
    }
}
