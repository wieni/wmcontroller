<?php

namespace Drupal\wmcontroller\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\wmcontroller\Controller\FrontController;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Alter canonical routes to use bundle-specific controllers.
 */
class InjectFrontControllerRouteEnhancer implements EnhancerInterface
{
    /** @var array */
    protected $settings;
    /** @var string */
    protected $frontController = FrontController::class;
    /** @var array */
    protected $ignoreRoutes = [];

    public function __construct(
        array $settings
    ) {
        $this->settings = $settings;

        if (isset($settings['frontcontroller'])) {
            $this->frontController = $settings['frontcontroller'];
        }

        if (isset($settings['ignore_routes'])) {
            $this->ignoreRoutes = $settings['ignore_routes'];
        }
    }

    public function enhance(array $defaults, Request $request)
    {
        $routeName = $defaults[RouteObjectInterface::ROUTE_NAME];

        if (!preg_match('#entity\..+\.canonical#', $routeName)) {
            return $defaults;
        }

        if (in_array($routeName, $this->ignoreRoutes, true)) {
            return $defaults;
        }

        if (isset($defaults['_controller'])) {
            $defaults['_original_controller'] = $defaults['_controller'];
        }

        $defaults['_controller'] = sprintf(
            '%s%s%s',
            $this->frontController,
            class_exists($this->frontController)
                ? '::' // FQN::method
                : ':', // servicename:method
            'forward'
        );

        return $defaults;
    }
}
