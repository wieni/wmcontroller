<?php

namespace Drupal\wmcontroller\Http\Policy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;

class OverrideSessionPolicy implements RequestPolicyInterface
{
    /** @var AccountProxyInterface */
    protected $account;

    protected $ignoreAuthenticatedUsers;
    protected $ignoredRoles;
    protected $tags;

    public function __construct(
        AccountProxyInterface $account,
        $tags,
        $ignoreAuthenticatedUsers,
        array $ignoredRoles = []
    ) {
        $this->tags = $tags;
        $this->account = $account;
        $this->ignoreAuthenticatedUsers = $ignoreAuthenticatedUsers;
        $this->ignoredRoles = $ignoredRoles;
    }

    public function check(Request $request)
    {
        if (!$this->tags || $this->ignoreAuthenticatedUsers) {
            return null;
        }

        if ((int) $this->account->id() === 1) {
            return null;
        }

        $account = $this->account->getAccount();
        $has = array_intersect($this->ignoredRoles, $account->getRoles());
        if (!empty($has)) {
            return null;
        }

        return static::ALLOW;
    }
}
