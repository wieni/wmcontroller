<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\wmcontroller\Controller\FrontController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteSubscriberBase;

/**
 * Alter entity.{node,taxonomy_term}.canonical routes to use bundle-specific
 * controllers.
 */
class InjectFrontControllerRouteSubscriber extends RouteSubscriberBase
{
    protected $settings;

    protected $frontController = FrontController::class;

    public function __construct(array $settings)
    {
        if (empty($settings['module'])) {
            throw new \Exception(
                'wmcontroller requires a non-empty module entry in wmcontroller.settings'
            );
        }

        if (isset($settings['frontcontroller'])) {
            $this->frontController = $settings['frontcontroller'];
        }

        $this->settings = $settings;
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
        $routes = [
            'node' => [
                'entity.node.canonical',
            ],
            'term' => [
                'entity.taxonomy_term.canonical',
            ],
        ];

        foreach ($routes as $methodName => $routeNames) {
            foreach ($routeNames as $routeName) {
                if ($detailRoute = $collection->get($routeName)) {
                    $this->alterRoute($detailRoute, $methodName);
                }
            }
        }
    }

    /**
     * Change a route's controller to a FrontController
     * that will delegate the request to a bundle-specific controller
     *
     * @param Route $route
     * @param $controllerMethod
     */
    protected function alterRoute(Route $route, $controllerMethod)
    {
        $defaults = $route->getDefaults();

        // Change the default controller to our own FrontController
        // The FrontController will delegate to a bundle-specific controller
        $defaults['_controller'] = sprintf(
            '%s%s%s',
            $this->frontController,
            class_exists($this->frontController)
                ? '::' // FQN::method
                : ':', // servicename:method
            $controllerMethod
        );

        // Add the namespace of where the bundle-specific controllers live
        // so the delegating FrontController has all the information it needs.
        $defaults['_controller_namespace'] = $this->getControllerNamespace();

        $route->setDefaults($defaults);
    }

    /**
     * Get the controller namespace of the bundle-specific controllers
     * For example: \Drupal\mymodule\Controller
     *
     * @return string
     */
    protected function getControllerNamespace()
    {
        $moduleName = $this->settings['module'];
        return "Drupal\\$moduleName\\Controller";
    }
}

