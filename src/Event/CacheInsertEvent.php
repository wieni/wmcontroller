<?php

namespace Drupal\wmcontroller\Event;

use Drupal\wmcontroller\Entity\Cache;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheInsertEvent extends Event
{
    /** @var Cache */
    protected $cache;
    /** @var string[] */
    protected $tags;
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;

    public function __construct(Cache $cache, array $tags, Request $request, Response $response)
    {
        $this->tags = $tags;
        $this->cache = $cache;
        $this->request = $request;
        $this->response = $response;
    }

    public function setCache(Cache $cache): void
    {
        $this->cache = $cache;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    /** @return string[] */
    public function &getTags(): array
    {
        return $this->tags;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
