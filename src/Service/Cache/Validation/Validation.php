<?php

namespace Drupal\wmcontroller\Service\Cache\Validation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\wmcontroller\Event\ValidationEvent;
use Drupal\wmcontroller\Service\Cache\Dispatcher;
use Drupal\wmcontroller\Service\Cache\EnrichRequest;
use Drupal\wmcontroller\WmcontrollerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Validation implements EventSubscriberInterface
{
    protected $cacheableStatusCodes = [
        Response::HTTP_OK => true,
        Response::HTTP_NON_AUTHORITATIVE_INFORMATION => true,
    ];

    protected $cacheableMethods = [
        Request::METHOD_GET => true,
        Request::METHOD_HEAD => true,
        Request::METHOD_OPTIONS => true,
    ];

    /** @var \Drupal\wmcontroller\Service\Cache\Dispatcher */
    protected $eventDispatcher;
    /** @var \Drupal\Core\PageCache\ResponsePolicyInterface */
    protected $cacheResponsePolicy;
    /** @var bool */
    protected $ignoreAuthenticatedUsers;
    /** @var bool */
    protected $storeResponse;
    /** @var bool */
    protected $storeTags;
    /** @var array */
    protected $ignoredRoles;

    public function __construct(
        Dispatcher $eventDispatcher,
        ResponsePolicyInterface $cacheResponsePolicy,
        $ignoreAuthenticatedUsers = true,
        $storeResponse,
        $storeTags,
        array $ignoredRoles = []
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->cacheResponsePolicy = $cacheResponsePolicy;
        $this->ignoreAuthenticatedUsers = $ignoreAuthenticatedUsers;
        $this->storeResponse = $storeResponse && $storeTags;
        $this->storeTags = $storeTags;
        $this->ignoredRoles = $ignoredRoles;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::VALIDATE_CACHEABILITY_REQUEST][] = 'onShouldIgnoreRequest';
        $events[WmcontrollerEvents::VALIDATE_CACHEABILITY_RESPONSE][] = 'onShouldIgnoreResponse';
        return $events;
    }

    /** @return CacheableRequestResult */
    public function shouldIgnoreRequest(Request $request)
    {
        // Don't even go through the motion if we are basically disabled
        if (!$this->storeResponse) {
            return new CacheableRequestResult(AccessResult::forbidden('Not storing any cache.'));
        }

        $event = $this->eventDispatcher->dispatchRequestCacheablityValidation(
            $request
        );
        return $event->result();
    }

    /** @return CacheableResponseResult */
    public function shouldIgnoreResponse(Request $request, Response $response)
    {
        // Don't even go through the motion if we are basically disabled
        if (!$this->storeResponse && !$this->storeTags) {
            // Neutral because the page might be cacheable, we just don't care.
            return new CacheableResponseResult(AccessResult::neutral());
        }

        $event = $this->eventDispatcher->dispatchResponseCacheablityValidation(
            $request,
            $response
        );
        return $event->result();
    }

    public function onShouldIgnoreRequest(ValidationEvent $event)
    {
        $request = $event->getRequest();

        $event->add($this->isCacheableMethod($request));
        $event->add($this->authenticationCheck($request));
        $event->add($this->roleCheck($request));
        $event->add($this->isNotAdmin($request));
    }

    public function onShouldIgnoreResponse(ValidationEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Run checks for the request
        // ( No POST, not logged in, roles, ... )
        $this->onShouldIgnoreRequest($event);

        // And additionally those for the response
        // ( No 5xx, no 'no_cache' on route, cache kill_switch, ... )
        $event->add($this->isCacheableStatusCode($response));
        $event->add($this->isCacheableAccordingToDrupal($request, $response));
    }

    protected function isAuthenticated(Request $request)
    {
        return $request->attributes->get(
            EnrichRequest::AUTHENTICATED,
            true
        );
    }

    protected function getUserId(Request $request)
    {
        return $request->attributes->get(
            EnrichRequest::UID,
            0
        );
    }

    protected function getRoles(Request $request)
    {
        return $request->attributes->get(
            EnrichRequest::ROLES,
            ['anonymous']
        );
    }

    protected function isCacheableMethod(Request $request)
    {
        return AccessResult::forbiddenIf(
            !isset($this->cacheableMethods[$request->getMethod()]),
            'Method not cacheable'
        );
    }

    protected function authenticationCheck(Request $request)
    {
        return AccessResult::forbiddenIf(
            $this->ignoreAuthenticatedUsers && $this->isAuthenticated($request),
            'Authenticated user'
        );
    }

    protected function roleCheck(Request $request)
    {
        return AccessResult::forbiddenIf(
            !$this->ignoreAuthenticatedUsers
            && $this->ignoredRoles
            && array_intersect($this->ignoredRoles, $this->getRoles($request)),
            'Ignored role'
        );
    }

    protected function isNotAdmin(Request $request)
    {
        return AccessResult::forbiddenIf(
            (int) $this->getUserId($request) === 1,
            'Administrator'
        );
    }

    protected function isCacheableStatusCode(Response $response)
    {
        $result = AccessResult::forbiddenIf(
            !isset($this->cacheableStatusCodes[$response->getStatusCode()]),
            'Non-cacheable status code'
        );
        return $result;
    }

    protected function isCacheableAccordingToDrupal(
        Request $request,
        Response $response
    ) {
        $cacheable = $this->cacheResponsePolicy->check(
            $response,
            $request
        );

        // Don't cache if Drupal thinks it's a bad idea to cache.
        // The cacheResponsePolicy by default has a few rules:
        // - page_cache_kill_switch triggers when drupal_get_message is used
        // - page_cache_no_cache_routes looks for the 'no_cache' route option
        // - page_cache_no_server_error makes sure we don't cache server errors
        // ...
        return AccessResult::forbiddenIf(
            $cacheable === ResponsePolicyInterface::DENY,
            'Drupal says no'
        );
    }
}