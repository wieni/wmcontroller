<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\wmcontroller\Controller\FrontController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteSubscriberBase;
use Psr\Log\LoggerInterface;

class InjectFrontControllerRouteSubscriber extends RouteSubscriberBase
{

    /** @var LoggerInterface */
    protected $logger;

    /** @var ImmutableConfig */
    protected $config;

    public function __construct(
        Config $config,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        // Default implementation (weight 0) doesn't suffice to
        // overwrite the defaults._controller of entity.taxonomy_term.canonical.
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
        return $events;
    }

    /**
     * @inheritdoc
     */
    protected function alterRoutes(RouteCollection $collection)
    {
        if (!$this->getControllerModule()) {
            $this->logger->notice(
                'No "wmcontroller.settings.module" config set. ' .
                'Aborting altering routes.' .
                PHP_EOL .
                'Please visit /admin/config/services/wmcontroller'
            );

            return;
        }

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
        $defaults['_controller'] = FrontController::class .
            '::' .
            $controllerMethod;

        // Add the namespace of where the bundle-specific controllers live
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
        $moduleName = $this->getControllerModule();
        return "Drupal\\$moduleName\\Controller";
    }

    /**
     * Get the module name where the bundle-specific controllers live
     *
     * @return string
     */
    protected function getControllerModule()
    {
        return $this->config->get('module') ?: '';
    }
}

