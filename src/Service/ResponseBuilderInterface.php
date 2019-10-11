<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

interface ResponseBuilderInterface
{
    public function createResponse(array $renderArray, Request $request = null, RouteMatchInterface $routeMatch = null);
}
