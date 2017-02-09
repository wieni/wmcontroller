<?php

namespace Drupal\wmcontroller\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

class PagerRewriteSubscriber extends RouteSubscriberBase
{
    const ROUTE_PARAM = 'wm_page';

    protected $routes;

    public static function getSubscribedEvents()
    {
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
        $events[KernelEvents::CONTROLLER][] = ['onController', 0];

        return $events;
    }

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    protected function alterRoutes(RouteCollection $collection)
    {
        foreach ($this->routes as $route) {
            if ($route = $collection->get($route)) {
                $route->setPath(
                    sprintf('%s/{%s}', $route->getPath(), self::ROUTE_PARAM)
                );
                $route->addDefaults([self::ROUTE_PARAM => 0]);
                $route->setOption('wmcontroller.pager', true);
                $route->addRequirements([self::ROUTE_PARAM => '\d+']);
            }
        }
    }

    public function onController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $request->query->set(
            'page',
            $request->attributes->get(self::ROUTE_PARAM, 0)
        );
    }
}

