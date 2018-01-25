<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
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

    /** @var \Drupal\Core\Config\Config */
    protected $config;

    public function __construct(
        ControllerResolverInterface $controllerResolver,
        Dispatcher $dispatcher,
        ConfigFactoryInterface $configFactory
    ) {
        $this->dispatcher = $dispatcher;
        $this->controllerResolver = $controllerResolver;
        $this->config = $configFactory->get('wmcontroller');
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('controller_resolver'),
            $container->get('wmcontroller.cache.dispatcher'),
            $container->get('config.factory')
        );
    }

    public function term(Request $request, EntityInterface $taxonomy_term)
    {
        return $this->forward($request, $taxonomy_term);
    }

    public function node(Request $request, EntityInterface $node)
    {
        return $this->forward($request, $node);
    }

    /**
     * Forward a request to a controller based on an entities bundle name
     */
    protected function forward(Request $request, EntityInterface $entity)
    {
        $this->request = $request;

        $controller = [$this->getController($entity), 'show'];

        // Check if the controller has a show method
        if (!is_callable($controller)) {
            throw new \RuntimeException(sprintf(
                'Class "%s" does not have a "%s()" method',
                get_class($controller[0]),
                $controller[1]
            ));
        }

        $this->dispatcher->dispatchMainEntity($entity);

        $language = $this->languageManager()->getCurrentLanguage();

        $redirectUntranslated = $this->config->get('redirect_untranslated') ?: false;
        $redirectPath = $this->config->get('redirect_untranslated_path') ?: '<front>';

        if ($redirectUntranslated && $entity->language()->getId() !== $language->getId()) {
            return $this->redirect(
                $redirectPath,
                [],
                [
                    'language' => $language
                ]
            );
        }

        return call_user_func_array(
            $controller,
            // @see \Drupal\Core\Controller\ControllerResolver::doGetArguments
            $this->controllerResolver->getArguments($request, $controller)
        );
    }

    /**
     * Locate and instantiate a controller based on an entity and it's bundle
     */
    protected function getController(EntityInterface $entity)
    {
        $controllerName = $this->camelize($entity->bundle()) . 'Controller';
        $namespace = $this->request->attributes->get('_controller_namespace');

        if (empty($namespace)) {
            throw new \RuntimeException('No controller namespace set');
        }

        $entityTypeId = $this->camelize($entity->getEntityTypeId());
        $fqn = "$namespace\\$entityTypeId\\$controllerName";

        if (!class_exists($fqn)) {
            throw new \RuntimeException(sprintf('Class "%s" not found', $fqn));
        }

        if (!is_subclass_of($fqn, ControllerBase::class)) {
            throw new NotFoundHttpException(
                sprintf(
                    'Class "%s" does not extend "%s"',
                    $fqn,
                    ControllerBase::class
                )
            );
        }

        return call_user_func([$fqn, 'create'], \Drupal::getContainer());
    }

    protected function camelize($input, $separator = '_')
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }
}

