<?php

namespace Drupal\wmcontroller\Http\Policy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

class OverrideSessionPolicy implements RequestPolicyInterface
{
    protected $ignoreAuthenticatedUsers;

    public function __construct($ignore)
    {
        $this->ignoreAuthenticatedUsers = $ignore;
    }

    public function check(Request $request)
    {
        if (!$this->ignoreAuthenticatedUsers) {
            return static::ALLOW;
        }
    }
}
