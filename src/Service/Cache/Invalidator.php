<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;

class Invalidator implements CacheTagsInvalidatorInterface
{
    /** @var StorageInterface */
    protected $storage;
    /** @var array */
    protected $ignores;

    public function __construct(StorageInterface $storage, $ignoredTags = [])
    {
        $this->storage = $storage;
        $this->ignores = $ignoredTags;
    }

    public function invalidateTags(array $tags)
    {
        $tags = array_filter($tags, [$this, 'filterIgnored']);

        $this->storage->expire(
            $this->storage->getByTags($tags)
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

