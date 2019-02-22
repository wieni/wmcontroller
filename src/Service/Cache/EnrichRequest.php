<?php

namespace Drupal\wmcontroller\Service\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\SessionConfigurationInterface;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class EnrichRequest
{
    const UID = '_wmcontroller.uid';
    const ROLES = '_wmcontroller.roles';
    const SESSION = '_wmcontroller.session';

    /** @var \SessionHandlerInterface */
    protected $sessionHandler;
    /** @var \Drupal\Core\Session\SessionConfigurationInterface */
    protected $sessionConfiguration;
    /** @var \Drupal\Core\Database\Connection */
    protected $db;
    /** @var bool */
    protected $ignoreAuthenticatedUsers;

    public function __construct(
        SessionHandlerInterface $sessionHandler,
        SessionConfigurationInterface $sessionConfiguration,
        Connection $db,
        $ignoreAuthenticatedUsers = true
    ) {
        $this->sessionHandler = $sessionHandler;
        $this->sessionConfiguration = $sessionConfiguration;
        $this->db = $db;
        $this->ignoreAuthenticatedUsers = $ignoreAuthenticatedUsers;
    }

    public function enrichRequest(Request $request)
    {
        if (
            $this->ignoreAuthenticatedUsers
            || !$this->sessionConfiguration->hasSession($request)
        ) {
            $request->attributes->set(static::SESSION, []);
            $request->attributes->set(static::UID, 0);
            $request->attributes->set(static::ROLES, ['anonymous']);
            return;
        }

        $options = $this->sessionConfiguration->getOptions($request);
        if (empty($options['name'])) {
            return;
        }

        $name = $options['name'];
        $ssid = $request->cookies->get($name);
        if (!$ssid) {
            return;
        }

        $session = $this->sessionHandler->read($ssid);
        if (!$session) {
            return;
        }
        $matches = [];
        preg_match_all('#([_\w]+)\|((.*?)}+)#', $session, $matches, PREG_SET_ORDER);

        $bags = [];
        foreach ($matches as $match) {
            if (empty($match[1]) || empty($match[2])) {
                continue;
            }
            try {
                $bags[$match[1]] = @unserialize($match[2], ['allowed_classes' => false]) ?: [];
            } catch (\Exception $e) {
                // noops
            }
        }

        $uid = $bags['_sf2_attributes']['uid'] ?? 0;
        $roles = [];
        try {
            $roles = $this->loadUserRoles($uid);
        } catch (\Exception $e) {
            // noop
        }

        $request->attributes->set(static::SESSION, $bags['_sf2_attributes'] ?? []);
        $request->attributes->set(static::UID, $uid);
        $request->attributes->set(static::ROLES, $roles);
    }

    protected function loadUserRoles($uid)
    {
        if ($uid == 0) {
            return ['anonymous'];
        }

        $q = $this->db->select('user__roles', 'ur')
            ->fields('ur', ['roles_target_id']);
        $q->condition('ur.entity_id', $uid);
        $q->groupBy('ur.entity_id');
        $q->groupBy('ur.roles_target_id');

        $roles = $q->execute()->fetchAll(\PDO::FETCH_COLUMN);
        if ($uid === 1 || $uid === '1') {
            $roles[] = 'administrator';
        }

        return $roles;
    }
}
