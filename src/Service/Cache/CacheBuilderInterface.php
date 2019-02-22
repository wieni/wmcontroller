<?php

namespace Drupal\wmcontroller\Service\Cache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CacheBuilderInterface
{
    /**
     * @param string $id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param string[] $tags
     *
     * @return \Drupal\wmcontroller\Entity\Cache
     */
    public function buildCacheEntity($id, Request $request, Response $response, array $tags = []);
}
