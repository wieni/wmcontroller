<?php

namespace Drupal\wmcontroller\Service\Maxage;

use Symfony\Component\HttpFoundation\Request;

interface MaxAgeInterface
{
    public function getMaxage(Request $request);
}