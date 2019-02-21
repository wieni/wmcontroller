<?php

namespace Drupal\wmcontroller\Service\Cache;

interface InvalidatorInterface
{
    // Lame I know. Had to do this to avoid a circular dependency meh
    public function setManager(Manager $manager);

    public function invalidateCacheTags(array $tags);
}