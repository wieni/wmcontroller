<?php

namespace Drupal\wmcontroller\Http;

use Symfony\Component\HttpFoundation\Response;

/**
 * CachedResponse implies that this response was already cached.
 */
class CachedResponse extends Response
{
}
