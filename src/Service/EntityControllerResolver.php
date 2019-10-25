<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityInterface;

class EntityControllerResolver implements EntityControllerResolverInterface
{
    /** @var ClassResolverInterface */
    protected $classResolver;
    /** @var array */
    protected $settings;

    public function __construct(
        ClassResolverInterface $classResolver,
        array $settings
    ) {
        $this->classResolver = $classResolver;
        $this->settings = $settings;
    }

    public function getController(EntityInterface $entity)
    {
        $controllerName = $this->camelize($entity->bundle()) . 'Controller';
        $namespace = $this->getNamespace();

        if (empty($namespace)) {
            throw new \RuntimeException('No controller namespace set');
        }

        $entityTypeId = $this->camelize($entity->getEntityTypeId());
        $fqn = "$namespace\\$entityTypeId\\$controllerName";

        if (!class_exists($fqn)) {
            throw new \RuntimeException(sprintf('Class "%s" not found', $fqn));
        }

        return $this->classResolver->getInstanceFromDefinition($fqn);
    }

    protected function getNamespace(): string
    {
        if (empty($this->settings['module'])) {
            throw new \Exception(
                'wmcontroller requires a non-empty module entry in wmcontroller.settings'
            );
        }

        return "Drupal\\{$this->settings['module']}\\Controller";
    }

    protected function camelize($input, $separator = '_')
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }
}
