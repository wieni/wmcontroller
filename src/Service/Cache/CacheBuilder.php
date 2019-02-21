<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\wmcontroller\Entity\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheBuilder implements CacheBuilderInterface, CacheSerializerInterface
{
    /** @var bool */
    protected $storeCache;
    /** @var array */
    protected $ignoredHeaders;

    public function __construct(
        $storeCache = true
    ) {
        $this->storeCache = $storeCache;
    }

    public function buildCacheEntity(
        $id,
        Request $request,
        Response $response,
        array $tags = []
    ) {
        $body = '';
        $headers = [];
        if ($this->storeCache) {
            $body = $response->getContent();
            $headers = $response->headers->all();
        }

        $ttl = $this->getMaxAge($response);

        return new Cache(
            $id,
            $request->getRequestUri(),
            $request->getMethod(),
            $body,
            $headers,
            time() + $ttl
        );
    }

    public function normalize(Cache $item)
    {
        return [
            'id' => $item->getId(),
            'method' => $item->getMethod(),
            'uri' => $item->getUri(),
            'content' => gzcompress($item->getBody()),
            'headers' => serialize($item->getHeaders()),
            'expiry' => $item->getExpiry(),
        ];
    }

    public function denormalize(array $row)
    {
        return new Cache(
            $row['id'],
            $row['uri'],
            $row['method'],
            empty($row['content']) ? null : gzuncompress($row['content']),
            empty($row['headers']) ? [] : unserialize($row['headers']),
            $row['expiry']
        );
    }

    protected function getMaxAge(Response $response)
    {
        /**
         * The Cache-Control header allows for extensions.
         *
         * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9.6
         * Unrecognized cache-directives MUST be ignored; it is assumed that
         * any cache-directive likely to be unrecognized by an HTTP/1.1 cache
         * will be combined with standard directives (or the response's default
         * cacheability) such that the cache behavior will remain minimally
         * correct even if the cache does not understand the extension(s).
         */
        if ($response->headers->hasCacheControlDirective('wm-s-maxage')) {
            return (int) $response->headers->getCacheControlDirective('wm-s-maxage');
        }

        return $response->getMaxAge();
    }
}