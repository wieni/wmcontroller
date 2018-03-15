<?php

namespace Drupal\wmcontroller\Service\Maxage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class MaxAgeManager implements MaxAgeInterface
{
    protected $strategy;
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    private $container;

    public function __construct(
        ContainerInterface $container,
        string $defaultStrategy
    ) {
        $this->container = $container;
        $this->setStrategy($defaultStrategy);
    }

    public function setStrategy($strategyId)
    {
        $this->strategy = $strategyId;
    }

    public function getStrategy()
    {
        return $this->strategy;
    }

    public function getMaxAge(Request $request)
    {
        return $this->container
            ->get($this->getStrategy())
            ->getMaxage($request);
    }
}