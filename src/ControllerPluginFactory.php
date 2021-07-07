<?php

namespace Drupal\wmcontroller;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin factory which passes a container to a create method.
 */
class ControllerPluginFactory extends DefaultFactory
{
    public function createInstance($plugin_id, array $configuration = [])
    {
        $plugin_definition = $this->discovery->getDefinition($plugin_id);
        $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);

        // If the plugin provides a factory method, pass the container to it.
        if (is_subclass_of($plugin_class, ContainerFactoryPluginInterface::class)) {
            return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
        }
        if (is_subclass_of($plugin_class, ContainerInjectionInterface::class)) {
            return $plugin_class::create(\Drupal::getContainer());
        }

        // Otherwise, create the plugin directly.
        return new $plugin_class($configuration, $plugin_id, $plugin_definition);
    }
}
