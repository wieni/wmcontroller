<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

class Invalidator implements CacheTagsInvalidatorInterface, InvalidatorInterface
{
    /** @var Manager */
    protected $manager;
    /** @var array */
    protected $ignores;

    public function __construct($ignoredTags = [])
    {
        $this->ignores = $ignoredTags;
    }

    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function invalidateTags(array $tags)
    {
        if (!$this->manager) {
            return;
        }

        $this->manager->invalidateCacheTags(
            array_filter($tags, [$this, 'filterIgnored'])
        );
    }

    public function invalidateCacheTags(array $tags)
    {
        if (!$this->manager) {
            return;
        }

        $this->manager->purgeByTags($tags);
    }

    protected function filterIgnored($tag)
    {
        foreach ($this->ignores as $re) {
            if (preg_match('#' . $re . '#', $tag)) {
                return false;
            }
        }
        return true;
    }
}

