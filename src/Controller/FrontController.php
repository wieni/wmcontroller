<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrontController extends ControllerBase
{

    /** @var ControllerResolverInterface */
    protected $controllerResolver;

    /** @var Request */
    protected $request;

    /** @var Dispatcher */
    protected $dispatcher;

    /**
     * AbstractFrontController constructor.
     *
     * @param ControllerResolverInterface $controllerResolver
     */
    public function __construct(
        ControllerResolverInterface $controllerResolver,
        Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
        $this->controllerResolver = $controllerResolver;
    }

    /**
     * @inheritdoc
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('controller_resolver'),
            $container->get('wmcontroller.cache.dispatcher')
        );
    }

    /**
     * Forward to a bundle-specific term controller
     *
     * @param Request $request
     * @param EntityInterface $taxonomy_term
     * @return mixed
     */
    public function term(Request $request, EntityInterface $taxonomy_term)
    {
        return $this->forward($request, $taxonomy_term);
    }

    /**
     * Forward to a bundle-specific node controller
     *
     * @param Request $request
     * @param EntityInterface $node
     * @return mixed
     */
    public function node(Request $request, EntityInterface $node)
    {
        return $this->forward($request, $node);
    }

    /**
     * Forward a request to a controller based on an entities bundle name
     *
     * @param Request $request
     * @param EntityInterface $entity
     * @return mixed
     */
    protected function forward(Request $request, EntityInterface $entity)
    {
        $this->request = $request;

        // Build a controller based on the bundle name
        $controller = [
            $this->getController($entity),
            $this->getControllerMethodName()
        ];

        // Check if the controller has a show method
        if (!is_callable($controller)) {
            throw new \RuntimeException(sprintf(
                'Class "%s" does not have a "%s()" method',
                get_class($controller[0]),
                $controller[1]
            ));
        }

        // Extract arguments from the $request object using the controllerResolver
        $arguments = $this->getArguments($request, $controller);

        $this->dispatcher->dispatchMainEntity($entity);

        // Call the controller
        return call_user_func_array($controller, $arguments);
    }

    /**
     * Let Drupal figure out which parameters to send to the controller
     * It uses reflection to figure out what the controller needs
     *
     * @see \Drupal\Core\Controller\ControllerResolver::doGetArguments
     * @param Request $request
     * @param array $controller
     * @return array
     */
    protected function getArguments(Request $request, array $controller)
    {
        return $this->controllerResolver->getArguments($request, $controller);
    }

    /**
     * Get the name of the method to call on the controller
     *
     * @return string
     */
    protected function getControllerMethodName()
    {
        return 'show';
    }

    /**
     * Get the namespace of the controllers
     * It looks for a _controller_namespace attribute set in the request
     *
     * @see \Drupal\wmcustom\Routing\AddBundleSpecificRoutesSubscriber::alterRoute
     * @return string
     */
    protected function getNamespace()
    {
        return $this->request->attributes->get('_controller_namespace', '');
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
                'Class "%s" not found',
                $class
            ));
        }
        if (!is_subclass_of($class, ControllerBase::class)) {
            throw new NotFoundHttpException(sprintf(
                'Class "%s" does not extend "%s"',
                $class,
                ControllerBase::class
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
        return "$namespace\\$entityTypeId\\$controllerName";
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
