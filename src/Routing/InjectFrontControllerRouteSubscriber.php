<?php

namespace Drupal\wmcontroller\Routing;

use Drupal\Core\Config\Config;
use Drupal\wmcontroller\Controller\FrontController;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteSubscriberBase;

class InjectFrontControllerRouteSubscriber extends RouteSubscriberBase
{

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        // Default implementation (weight 0) doesn't suffice to
        // overwrite the defaults._controller of entity.taxonomy_term.canonical.
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -9999];
        return $events;
    }

    /**
     * @inheritdoc
     */
    protected function alterRoutes(RouteCollection $collection)
    {
        if (!$this->getControllerModule()) {
            $this->getLogger()->notice(
                'No "wmcontroller.settings.module" config set. Aborting altering routes' . PHP_EOL
                . 'Please visit /admin/config/services/wmcontroller'
            );
            return;
        }
        $this->alterNodeRoutes($collection);
        $this->alterTaxonomyRoutes($collection);
    }

    /**
     * Change node routes
     *
     * @param RouteCollection $collection
     */
    protected function alterNodeRoutes(RouteCollection $collection)
    {
        $detailRoute = $collection->get('entity.node.canonical');
        $this->alterRoute($detailRoute, 'node');
        // todo: Alter other node routes?
    }

    /**
     * Change taxonomy routes
     *
     * @param RouteCollection $collection
     */
    protected function alterTaxonomyRoutes(RouteCollection $collection)
    {
        $detailRoute = $collection->get('entity.taxonomy_term.canonical');
        $this->alterRoute($detailRoute, 'term');
        // todo: Alter other term routes?
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
        $defaults['_controller'] = FrontController::class . '::' . $controllerMethod;

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
        return $this->getConfig()->get('module') ?: '';
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return \Drupal::service('wmcontroller.logger');
    }

    /**
     * @return Config
     */
    private function getConfig()
    {
        return \Drupal::service('wmcontroller.config');
    }
}
