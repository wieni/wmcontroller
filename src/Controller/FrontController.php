<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;
use Drupal\wmcontroller\Service\EntityControllerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    /** @var Dispatcher */
    protected $dispatcher;
    /** @var RendererInterface */
    protected $renderer;
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
        $instance->dispatcher = $container->get('wmcontroller.cache.dispatcher');
        $instance->renderer = $container->get('renderer');
        $instance->settings = $container->getParameter('wmcontroller.settings');

        if (isset($instance->settings['404_when_not_translated'])) {
            $instance->throw404WhenNotTranslated = $instance->settings['404_when_not_translated'];
        }

        return $instance;
    }

    /** Forward a request to a controller based on an entities bundle name */
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
            $controller = $this->controllerResolver->getControllerFromDefinition($controller);
        }

        $this->dispatcher->dispatchMainEntity($entity);

        $context = new RenderContext();
        $response = $this->renderer->executeInRenderContext($context, function () use ($request, $controller) {
            return call_user_func_array(
                $controller,
                $this->argumentResolver->getArguments($request, $controller)
            );
        });

        // If there is metadata left on the context, apply it on the response.
        if (!$context->isEmpty()) {
            $metadata = $context->pop();

            if (is_array($response)) {
                BubbleableMetadata::createFromRenderArray($response)
                    ->merge($metadata)
                    ->applyTo($response);
            }

            if ($response instanceof CacheableResponseInterface) {
                $response->addCacheableDependency($metadata);
            }

            if ($response instanceof AttachmentsInterface) {
                $response->addAttachments($metadata->getAttachments());
            }
        }

        return $response;
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
