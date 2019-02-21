<?php

namespace Drupal\wmcontroller\Service\Maxage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MaxAgeInterface
{
    public function getMaxage(Request $request, Response $response);
}