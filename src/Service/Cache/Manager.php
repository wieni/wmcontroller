<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Manager implements CacheTagsInvalidatorInterface
{
    /** @var \Drupal\wmcontroller\Service\Cache\Dispatcher */
    protected $eventDispatcher;
    /** @var \Drupal\wmcontroller\Service\Cache\Storage\StorageInterface */
    protected $storage;
    /** @var \Drupal\wmcontroller\Service\Cache\InvalidatorInterface */
    protected $invalidator;
    /** @var \Drupal\wmcontroller\Service\Cache\CacheKeyGeneratorInterface */
    protected $cacheKeyGenerator;
    /** @var \Drupal\wmcontroller\Service\Cache\CacheBuilderInterface */
    protected $cacheBuilder;
    /** @var bool */
    protected $storeCache;
    /** @var bool */
    protected $storeTags;
    /** @var int */
    protected $maxPurgesPerInvalidation;
    /** @var string[] */
    protected $ignoredCacheTags;
    /** @var string[] */
    protected $flushTriggerTags;

    public function __construct(
        Dispatcher $eventDispatcher,
        StorageInterface $storage,
        InvalidatorInterface $invalidator,
        CacheKeyGeneratorInterface $cacheKeyGenerator,
        CacheBuilderInterface $cacheBuilder,
        $storeCache,
        $storeTags,
        $maxPurgesPerInvalidation,
        array $ignoredCacheTags,
        array $flushTriggerTags
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->storage = $storage;
        $this->invalidator = $invalidator;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
        $this->cacheBuilder = $cacheBuilder;
        $this->storeCache = $storeCache && $storeTags;
        $this->storeTags = $storeTags;
        $this->maxPurgesPerInvalidation = $maxPurgesPerInvalidation;
        $this->ignoredCacheTags = array_filter($ignoredCacheTags);
        $this->flushTriggerTags = array_filter($flushTriggerTags);
    }

    public function get(Request $request)
    {
        if (!$this->storeCache) {
            throw new NoSuchCacheEntryException('cache_disabled');
        }

        return $this->storage->load(
            $this->cacheKeyGenerator->generateCacheKey($request)
        );
    }

    public function store(Request $request, Response $response, array $tags)
    {
        if (!$this->storeTags) {
            return;
        }

        $cache = $this->cacheBuilder->buildCacheEntity(
            $this->cacheKeyGenerator->generateCacheKey($request),
            $request,
            $response,
            $tags
        );

        $event = $this->eventDispatcher->dispatchCacheInsertEvent(
            $cache,
            $request,
            $response,
            $tags
        );

        if ($event->getCache()) {
            $this->storage->set($event->getCache(), $event->getTags());
        }
    }

    public function invalidateTags(array $tags)
    {
        $filter = function ($tag) {
            foreach ($this->ignoredCacheTags as $re) {
                if (preg_match('#' . $re . '#', $tag)) {
                    return false;
                }
            }
            return true;
        };

        // Remove ignored tags
        $tags = array_filter($tags, $filter);

        // Check if any tag matches a flushTriggerTags regex
        // If so, flush the entire cache instead.
        foreach ($tags as $tag) {
            foreach ($this->flushTriggerTags as $re) {
                if (preg_match('#' . $re . '#', $tag)) {
                    $this->storage->flush();
                    return;
                }
            }
        }

        $this->invalidator->invalidateCacheTags($tags);
    }
}
