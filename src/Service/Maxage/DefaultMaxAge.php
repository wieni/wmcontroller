<?php

namespace Drupal\wmcontroller\Service\Maxage;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DefaultMaxAge implements EventSubscriberInterface, MaxAgeInterface
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
            !$headers->hasCacheControlDirective('maxage')
            && !$headers->hasCacheControlDirective('s-maxage')
        ) {
            return;
        }

        $this->explicitMaxAges = [
            'maxage' => $headers->getCacheControlDirective('maxage'),
            's-maxage' => $headers->getCacheControlDirective('s-maxage'),
        ];
    }

    public function onMainEntity(MainEntityEvent $event)
    {
        $this->mainEntity = $event->getEntity();
    }

    public function getMaxage(Request $request)
    {
        if (!empty($this->explicitMaxAges)) {
            return $this->explicitMaxAges;
        }

        if ($entityExpiry = $this->getMaxAgesForMainEntity()) {
            return $entityExpiry;
        }

        $smax = $request->attributes->get('_smaxage', 0);
        $max = $request->attributes->get('_maxage', 0);
        if ($smax || $max) {
            return ['s-maxage' => $smax, 'maxage' => $max];
        }

        $path = $request->getPathInfo();
        foreach ($this->expiries['paths'] as $re => $definition) {
            // # should be safe... I guess
            if (!preg_match('#' . $re . '#', $path)) {
                continue;
            }

            return $definition;
        }

        return ['s-maxage' => 0, 'maxage' => 0];
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