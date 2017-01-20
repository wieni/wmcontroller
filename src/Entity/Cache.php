<?php

namespace Drupal\wmcontroller\Entity;

use Drupal\wmcontroller\Http\CachedResponse;

class Cache
{
    protected $body;
    protected $headers;

    /** @var CachedResponse */
    protected $response;

    public function __construct($body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * @return CachedResponse
     */
    public function toResponse()
    {
        if (isset($this->response)) {
            return $this->response;
        }

        $this->response = new CachedResponse(
            $this->body,
            CachedResponse::HTTP_OK,
            $this->headers
        );

        return $this->response;
    }
}

