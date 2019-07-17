<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaxAgeDecider implements EventSubscriberInterface, MaxAgeInterface
{
    protected $expiries;
    /** @var \Drupal\Core\Entity\EntityInterface */
    protected $mainEntity;
    protected $explicitMaxAges;

    public function __construct(array $expiries)
    {
        $this->expiries = $expiries + ['paths' => [], 'entities' => []];
    }

    public static function getSubscribedEvents()
    {
        $events[KernelEvents::RESPONSE][] = ['onResponseEarly', 255];
        $events[WmcontrollerEvents::MAIN_ENTITY_RENDER][] = ['onMainEntity', 0];
        return $events;
    }

    public function onResponseEarly(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        if (
            !$headers->hasCacheControlDirective('max-age')
            && !$headers->hasCacheControlDirective('s-maxage')
            && !$headers->hasCacheControlDirective('wm-s-maxage')
        ) {
            return;
        }

        $this->explicitMaxAges = array_filter(
            [
                'maxage' => $headers->getCacheControlDirective('max-age'),
                's-maxage' => $headers->getCacheControlDirective('s-maxage'),
                'wm-s-maxage' => $headers->getCacheControlDirective('wm-s-maxage'),
            ],
            'strlen' // Keeps 0, but removes NULL
        );
    }

    public function onMainEntity(MainEntityEvent $event)
    {
        $this->mainEntity = $event->getEntity();
    }

    public function getMaxage(Request $request, Response $response)
    {
        $explicit = $this->explicitMaxAges ?: [];

        if (isset($explicit['maxage']) || isset($explicit['s-maxage'])) {
            return $explicit;
        }

        $smax = $request->attributes->get('_smaxage', 0);
        $max = $request->attributes->get('_maxage', 0);
        $wmmax = $request->attributes->get('_wmsmaxage', null);
        if ($smax || $max) {
            return $explicit + [
                's-maxage' => $smax,
                'maxage' => $max,
                'wm-s-maxage' => $wmmax
            ];
        }

        if ($entityExpiry = $this->getMaxAgesForMainEntity()) {
            return $explicit + $entityExpiry;
        }

        $path = $request->getPathInfo();
        foreach ($this->expiries['paths'] as $re => $definition) {
            // # should be safe... I guess
            if (!preg_match('#' . $re . '#', $path)) {
                continue;
            }

            return $explicit + $definition;
        }

        return $explicit + ['s-maxage' => 0, 'maxage' => 0, 'wm-s-maxage' => $wmmax];
    }

    protected function getMaxAgesForMainEntity()
    {
        if (!isset($this->mainEntity)) {
            return null;
        }

        $type = $this->mainEntity->getEntityTypeId();
        if (!isset($this->expiries['entities'][$type])) {
            return null;
        }

        $bundleDefs = $this->expiries['entities'][$type];

        $bundle = $this->mainEntity->bundle();

        if (isset($bundleDefs['_default'])) {
            $bundleDefs += [$bundle => $bundleDefs['_default']];
        }

        return $bundleDefs[$bundle];
    }

}