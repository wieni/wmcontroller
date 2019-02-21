<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\wmcontroller\Service\Cache\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Manager
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

    public function __construct(
        Dispatcher $eventDispatcher,
        StorageInterface $storage,
        InvalidatorInterface $invalidator,
        CacheKeyGeneratorInterface $cacheKeyGenerator,
        CacheBuilderInterface $cacheBuilder,
        $storeCache,
        $storeTags
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->storage = $storage;
        $this->invalidator = $invalidator;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
        $this->cacheBuilder = $cacheBuilder;
        $this->storeCache = $storeCache && $storeTags;
        $this->storeTags = $storeTags;

        // Circular dependency is shit
        $invalidator->setManager($this);
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

    public function setInvalidator(InvalidatorInterface $invalidator)
    {
        $this->invalidator = $invalidator;
    }

    public function invalidateCacheTags(array $tags)
    {
        if (!$this->invalidator) {
            return;
        }
        $this->invalidator->invalidateCacheTags($tags);
    }

    public function purgeByTags(array $tags)
    {
        $this->storage->remove(
            $this->storage->getByTags($tags)
        );
    }

    public function purge($amount)
    {
        $this->storage->remove(
            $this->storage->getExpired($amount)
        );
    }

    public function flush()
    {
        $this->storage->flush();
    }
}
