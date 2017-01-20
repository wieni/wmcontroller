<?php

namespace Drupal\wmcontroller\Exception;

class NoSuchCacheEntryException extends \RuntimeException
{
    public function __construct($method, $path, $message = '')
    {
        parent::__construct(
            sprintf(
                '%sNo cache entry found for %s: "%s"',
                $message ? $message . ' => ' : '',
                $method,
                $path
            )
        );
    }
}

