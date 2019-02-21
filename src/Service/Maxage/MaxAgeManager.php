<?php

namespace Drupal\wmcontroller\Service\Maxage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function getMaxAge(Request $request, Response $response)
    {
        return $this->container
            ->get($this->getStrategy())
            ->getMaxage($request, $response);
    }
}