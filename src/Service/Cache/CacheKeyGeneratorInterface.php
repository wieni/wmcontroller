<?php

namespace Drupal\wmcontroller\Service\Cache;

use Symfony\Component\HttpFoundation\Request;

interface CacheKeyGeneratorInterface
{
    public function generateCacheKey(Request $request);
}
