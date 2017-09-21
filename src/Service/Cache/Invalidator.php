<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

class Invalidator implements CacheTagsInvalidatorInterface
{
    /** @var Manager */
    protected $manager;
    /** @var array */
    protected $ignores;

    public function __construct(Manager $manager, $ignoredTags = [])
    {
        $this->manager = $manager;
        $this->ignores = $ignoredTags;
    }

    public function invalidateTags(array $tags)
    {
        $this->manager->purgeByTags(
            array_filter($tags, [$this, 'filterIgnored'])
        );
    }

    private function filterIgnored($tag)
    {
        foreach ($this->ignores as $re) {
            if (preg_match('#' . $re . '#', $tag)) {
                return false;
            }
        }
        return true;
    }
}

