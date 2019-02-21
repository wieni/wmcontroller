<?php

namespace Drupal\wmcontroller\Service\Cache\Validation;

use Drupal\Core\Access\AccessResultInterface;

abstract class ValidationResult
{
    /** @var \Drupal\Core\Access\AccessResultInterface */
    protected $result;

    public function __construct(AccessResultInterface $result)
    {
        $this->result = $result;
    }

    public function result($method)
    {
        if (!is_callable([$this, $method])) {
            return false;
        }
        return (bool) $this->$method();
    }
}