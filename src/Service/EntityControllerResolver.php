<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\ControllerPluginManager;

class EntityControllerResolver implements EntityControllerResolverInterface
{
    /** @var ControllerPluginManager */
    protected $pluginManager;

    public function __construct(
        ControllerPluginManager $pluginManager
    ) {
        $this->pluginManager = $pluginManager;
    }

    public function getController(EntityInterface $entity)
    {
        try {
            $id = implode('.', [$entity->getEntityTypeId(), $entity->bundle()]);
            return $this->pluginManager->createInstance($id);
        } catch (PluginNotFoundException $e) {
            throw new \RuntimeException('No bundle-specific controller is provided.', $e->getCode(), $e);
        }
    }
}
