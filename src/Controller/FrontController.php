<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;
use Drupal\wmcontroller\Service\EntityControllerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrontController
{
    /** @var EntityControllerResolverInterface */
    protected $entityControllerResolver;
    /** @var ArgumentResolverInterface */
    protected $argumentResolver;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var Dispatcher */
    protected $dispatcher;
    /** @var array */
    protected $settings;
    /** @var bool */
    protected $throw404WhenNotTranslated = true;

    /** @var Request */
    protected $request;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->entityControllerResolver = $container->get('wmcontroller.entity_controller_resolver');
        $instance->argumentResolver = $container->get('http_kernel.controller.argument_resolver');
        $instance->languageManager = $container->get('language_manager');
        $instance->dispatcher = $container->get('wmcontroller.cache.dispatcher');
        $instance->settings = $container->getParameter('wmcontroller.settings');

        if (isset($instance->settings['404_when_not_translated'])) {
            $instance->throw404WhenNotTranslated = $instance->settings['404_when_not_translated'];
        }

        return $instance;
    }

    /**
     * Forward a request to a controller based on an entities bundle name
     */
    public function forward(Request $request)
    {
        $routeName = $request->attributes->get('_route');
        preg_match('/entity\.(?<entityTypeId>.+)\.canonical/', $routeName, $matches);
        $entity = $request->attributes->get($matches['entityTypeId']);

        $this->validateLangcode($entity);
        $this->request = $request;

        try {
            $controller = [$this->entityControllerResolver->getController($entity), 'show'];
        } catch (\RuntimeException $e) {
            $controller = $request->attributes->get('_original_controller');
        }

        $this->dispatcher->dispatchMainEntity($entity);

        return call_user_func_array(
            $controller,
            $this->argumentResolver->getArguments($request, $controller)
        );
    }

    protected function validateLangcode(EntityInterface $entity): void
    {
        $language = $this->languageManager->getCurrentLanguage();
        $isMultiLang = count($this->languageManager->getLanguages()) > 1;

        if (
            $isMultiLang
            && $this->throw404WhenNotTranslated
            && $entity->language()->getId() !== $language->getId()
        ) {
            throw new NotFoundHttpException("Entity is not translated in the current language ({$language->getName()}).");
        }
    }
}

