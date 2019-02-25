<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

class CacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /** @var bool */
    protected $ignoreAuthenticatedUsers;
    /** @var array */
    protected $groupedRoles;
    /** @var array */
    protected $whitelistedQueryParams;

    public function __construct(
        $ignoreAuthenticatedUsers = true,
        array $groupedRoles = [],
        array $whitelistedQueryParams = []
    ) {
        $this->ignoreAuthenticatedUsers = $ignoreAuthenticatedUsers;
        $this->groupedRoles = $groupedRoles;
        $this->whitelistedQueryParams = $whitelistedQueryParams;
    }

    public function generateCacheKey(Request $request)
    {
        $uri = $this->getRequestUri($request);
        if ($roles = $this->getRoles($request)) {
            $uri .= '#roles=' . implode(',', $roles);
        }
        return sha1($uri);
    }

    protected function getRequestUri(Request $request)
    {
        $uri = $request->getSchemeAndHttpHost() .
            $request->getBaseUrl() .
            $request->getPathInfo();

        $query = [];
        parse_str($request->getQueryString() ?: '', $query);
        $query = array_intersect_key(
            $query,
            // Todo: whitelist-per request ( routing hasn't happened yet, so can't use route object )
            // perhaps path-based regex like we do for max-ages
            array_flip($this->whitelistedQueryParams)
        );

        $query = http_build_query($query);
        if ($query) {
            $uri .= '?' . $query;
        }

        return $uri;
    }

    protected function getRoles(Request $request)
    {
        if ($this->ignoreAuthenticatedUsers) {
            return [];
        }

        $uid = (int) $request->attributes->get(EnrichRequest::UID, 0);
        $roles = $request->attributes->get(EnrichRequest::ROLES, []);

        if ($uid === 1) {
            $roles[] = 'administrator';
        }
        if ($uid === 0) {
            $roles[] = AccountInterface::ANONYMOUS_ROLE;
        }

        return $this->groupRoles($roles);
    }

    protected function groupRoles(array $roles)
    {
        if (!$roles) {
            return [];
        }

        $names = [];
        foreach ($this->groupedRoles as $group) {
            $strict = $group['strict'] ?? false;
            $has = array_intersect($roles, $group['roles'] ?? []);
            if (
                (!$strict && $has)
                || ($strict && count($has) === count($roles))
            ) {
                $names[] = $group['name'];
            }
        }
        return $names;
    }
}