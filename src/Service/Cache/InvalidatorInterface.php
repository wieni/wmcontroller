<?php

namespace Drupal\wmcontroller\Service\Cache;

interface InvalidatorInterface
{
    public function invalidateCacheTags(array $tags);
}
