<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait RedirectBuilderTrait
{
    /**
     * Returns a redirect response object for the specified route.
     *
     * @param string $routeName
     *   The name of the route to which to redirect.
     * @param array $routeParameters
     *   (optional) Parameters for the route.
     * @param array $options
     *   (optional) An associative array of additional options.
     * @param int $status
     *   (optional) The HTTP redirect status code for the redirect. The default is
     *   302 Found.
     *
     * @return RedirectResponse
     *   A redirect response object that may be returned by the controller.
     */
    protected function redirect($routeName, array $routeParameters = [], array $options = [], $status = 302)
    {
        $url = Url::fromRoute($routeName, $routeParameters, $options)
            ->setAbsolute(true)
            ->toString();

        return new RedirectResponse($url, $status);
    }
}
