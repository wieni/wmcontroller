<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\Service\EntityControllerResolverInterface;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmtwig\ViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrontController implements ContainerInjectionInterface
{
    /** @var EntityControllerResolverInterface */
    protected $entityControllerResolver;
    /** @var ControllerResolverInterface */
    protected $controllerResolver;
    /** @var ArgumentResolverInterface */
    protected $argumentResolver;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
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
        $instance->controllerResolver = $container->get('controller_resolver');
        $instance->argumentResolver = $container->get('http_kernel.controller.argument_resolver');
        $instance->languageManager = $container->get('language_manager');
        $instance->eventDispatcher = $container->get('event_dispatcher');
        $instance->settings = $container->getParameter('wmcontroller.settings');

        if (isset($instance->settings['404_when_not_translated'])) {
            $instance->throw404WhenNotTranslated = $instance->settings['404_when_not_translated'];
        }

        return $instance;
    }

    /** Forward a request to a controller based on an entities bundle name */
    public function forward(Request $request)
    {
        $this->request = $request;

        $controller = null;
        $routeName = $request->attributes->get('_route');

        preg_match('/entity\.(?<entityTypeId>.+)\.(canonical|preview_link)$/', $routeName, $matches);

        $entityTypeId = $matches['entityTypeId'] ?? null;
        $entity = $request->attributes->get($entityTypeId);

        if ($routeName === 'entity.node.preview') {
            $entity = $request->attributes->get('node_preview');
        }

        if ($entity) {
            $this->validateLangcode($entity);

            try {
                $controller = [$this->entityControllerResolver->getController($entity), 'show'];
            } catch (\RuntimeException $e) {
                $controller = $request->attributes->get('_original_controller');
                $controller = $this->controllerResolver->getControllerFromDefinition($controller);
            }

            $event = new MainEntityEvent($entity);
            $this->eventDispatcher->dispatch(
                WmcontrollerEvents::MAIN_ENTITY_RENDER,
                $event
            );
        } else {
            $controller = $request->attributes->get('_original_controller');
            $controller = $this->controllerResolver->getControllerFromDefinition($controller);
        }

        $arguments = $this->argumentResolver->getArguments($request, $controller);
        $result = call_user_func_array($controller, $arguments);

        /**
         * Early conversion of ViewBuilder to render array to
         * make sure bubbleable metadata is not lost.
         *
         * @see \Drupal\Core\EventSubscriber\EarlyRenderingControllerWrapperSubscriber
         */
        if ($result instanceof ViewBuilder) {
            // Replace the controller result with a render-array
            $result = $result->toRenderArray();
        }

        return $result;
    }

    protected function validateLangcode(EntityInterface $entity): void
    {
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
        $isMultiLang = count($this->languageManager->getLanguages()) > 1;

        if (
            $isMultiLang
            && $this->throw404WhenNotTranslated
            && $entity instanceOf TranslatableInterface
            && $entity->isTranslatable()
            && $entity->language()->getId() !== $language->getId()
        ) {
            throw new NotFoundHttpException("Entity is not translated in the current language ({$language->getName()}).");
        }
    }
}
