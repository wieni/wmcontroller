<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;

use Drupal\wmcontroller\Service\EntityControllerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrontController extends ControllerBase
{
    /** @var ArgumentResolverInterface */
    protected $argumentResolver;

    /** @var EntityControllerResolverInterface */
    protected $entityControllerResolver;

    /** @var Request */
    protected $request;

    /** @var Dispatcher */
    protected $dispatcher;

    /** @var array */
    protected $settings;

    protected $throw404WhenNotTranslated = true;

    public function __construct(
        EntityControllerResolverInterface $entityControllerResolver,
        ArgumentResolverInterface $argumentResolver,
        Dispatcher $dispatcher,
        array $settings
    ) {
        $this->entityControllerResolver = $entityControllerResolver;
        $this->argumentResolver = $argumentResolver;
        $this->dispatcher = $dispatcher;
        $this->settings = $settings;

        if (isset($this->settings['404_when_not_translated'])) {
            $this->throw404WhenNotTranslated = $this->settings['404_when_not_translated'];
        }
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('wmcontroller.entity_controller_resolver'),
            $container->get('http_kernel.controller.argument_resolver'),
            $container->get('wmcontroller.cache.dispatcher'),
            $container->getParameter('wmcontroller.settings')
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
        $this->validateLangcode($entity);
        $this->request = $request;

        $controller = [$this->entityControllerResolver->getController($entity), 'show'];

        // Check if the controller has a show method
        if (!is_callable($controller)) {
            throw new \RuntimeException(sprintf(
                'Class "%s" does not have a "%s()" method',
                get_class($controller[0]),
                $controller[1]
            ));
        }

        $this->dispatcher->dispatchMainEntity($entity);

        return call_user_func_array(
            $controller,
            $this->argumentResolver->getArguments($request, $controller)
        );
    }

    protected function validateLangcode(EntityInterface $entity)
    {
        $langcode = $this->languageManager()->getCurrentLanguage()->getId();
        $isMultiLang = count($this->languageManager()->getLanguages()) > 1;

        if (
            $isMultiLang
            && $this->throw404WhenNotTranslated
            && $entity->language()->getId() !== $langcode
        ) {
            throw new NotFoundHttpException();
        }
    }
}

