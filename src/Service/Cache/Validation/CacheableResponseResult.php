<?php

namespace Drupal\wmcontroller\Service\Cache\Validation;

class CacheableResponseResult extends ValidationResult
{
    const ALLOW_STORE = 'allowStoreResponse';
    const ALLOW_CACHED = 'allowCached';

    /**
     * Returns true if nobody said it's forbidden to cache this.
     *
     * @return bool
     */
    public function allowCached()
    {
        return $this->result->isForbidden() === false;
    }
}