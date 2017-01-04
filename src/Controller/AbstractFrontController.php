<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractFrontController extends ControllerBase
{
    /**
     * Forward to a bundle-specific controller
     *
     * @param EntityInterface $node
     * @param string $mode
     * @return array
     */
    public function show(EntityInterface $node, $mode = 'full')
    {
        // Build a controller based on the bundle name
        $controller = $this->getController($node);

        // Check if the controller has a show method
        if (!is_callable([$controller, 'show'])) {
            throw new \RuntimeException(sprintf(
                'Class "%s" does not have a show() method',
                get_class($controller)
            ));
        }

        // Call the controller
        return $controller->show($node, $mode);
    }

    /**
     * Get the namespace of the controllers
     *
     * @return string
     */
    protected function getNamespace()
    {
        return substr(static::class, 0, strrpos(static::class, '\\', -1));
    }

    /**
     * Locate and instantiate a controller based on an entity and it's bundle
     *
     * @param EntityInterface $entity
     * @param string $namespace
     * @return mixed
     */
    protected function getController(EntityInterface $entity, $namespace = '')
    {
        $controllerName = $this->camelize($entity->bundle()) . 'Controller';
        $namespace = $namespace ?: $this->getNamespace();

        if (empty($namespace)) {
            throw new \RuntimeException('No controller namespace set');
        }

        $class = $this->getFullClassName($entity, $namespace, $controllerName);
        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf(
                'Class "%s" not found', $class
            ));
        }
        if (!is_subclass_of($class, ControllerBase::class)) {
            throw new NotFoundHttpException(sprintf(
                'Class "%s" does not extend "%s"', $class, ControllerBase::class
            ));
        }

        return call_user_func([$class, 'create'], \Drupal::getContainer());
    }

    /**
     * Get the FQN class name of the desired controller
     *
     * @param EntityInterface $entity
     * @param string $namespace
     * @param string $controllerName
     * @return string
     */
    protected function getFullClassName(EntityInterface $entity, $namespace, $controllerName)
    {
        $entityTypeId = $this->camelize($entity->getEntityTypeId());
        return $namespace . '\\' . $entityTypeId . '\\' . $controllerName;
    }

    /**
     * Camelize a string
     *
     * @param string $input
     * @param string $separator
     * @return string
     */
    protected function camelize($input, $separator = '_')
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }
}