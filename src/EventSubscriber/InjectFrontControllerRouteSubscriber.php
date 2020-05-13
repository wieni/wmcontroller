<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\wmcontroller\Controller\FrontController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter canonical routes to use bundle-specific controllers.
 */
class InjectFrontControllerRouteSubscriber extends RouteSubscriberBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var array */
    protected $settings;
    /** @var string */
    protected $frontController = FrontController::class;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        array $settings
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->settings = $settings;

        if (isset($settings['frontcontroller'])) {
            $this->frontController = $settings['frontcontroller'];
        }
    }

    public static function getSubscribedEvents()
    {
        // Default implementation (weight 0) doesn't suffice to
        // overwrite the defaults._controller of entity.taxonomy_term.canonical.
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
        return $events;
    }

    protected function alterRoutes(RouteCollection $collection)
    {
        $definitions = $this->entityTypeManager->getDefinitions();

        foreach ($definitions as $definition) {
            if ($route = $collection->get("entity.{$definition->id()}.canonical")) {
                $this->alterRoute($route);
            }
        }
    }

    protected function alterRoute(Route $route): void
    {
        $defaults = $route->getDefaults();

        $defaults['_original_controller'] = $defaults['_controller'];
        $defaults['_controller'] = sprintf(
            '%s%s%s',
            $this->frontController,
            class_exists($this->frontController)
                ? '::' // FQN::method
                : ':', // servicename:method
            'forward'
        );

        $route->setDefaults($defaults);
    }
}
