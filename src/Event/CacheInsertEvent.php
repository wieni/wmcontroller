<?php

namespace Drupal\wmcontroller\Event;

use Drupal\wmcontroller\Entity\Cache;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheInsertEvent extends Event
{
    /** @var \Drupal\wmcontroller\Entity\Cache */
    protected $cache;
    /** @var string[] */
    protected $tags;
    /** @var \Symfony\Component\HttpFoundation\Request */
    protected $request;
    /** @var \Symfony\Component\HttpFoundation\Response */
    protected $response;

    public function __construct(Cache $cache, array $tags, Request $request, Response $response)
    {
        $this->tags = $tags;
        $this->cache = $cache;
        $this->request = $request;
        $this->response = $response;
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /** @return Cache */
    public function getCache()
    {
        return $this->cache;
    }

    /** @return string[] */
    public function &getTags()
    {
        return $this->tags;
    }

    /** @return \Symfony\Component\HttpFoundation\Request */
    public function getRequest()
    {
        return $this->request;
    }

    /** @return \Symfony\Component\HttpFoundation\Response */
    public function getResponse()
    {
        return $this->response;
    }
}
