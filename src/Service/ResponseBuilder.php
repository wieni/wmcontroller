<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Render\MainContent\HtmlRenderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ResponseBuilder
{
    /** @var \Drupal\Core\Render\MainContent\HtmlRenderer */
    private $renderer;
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $stack;
    /** @var \Drupal\Core\Routing\RouteMatchInterface */
    private $routeMatch;

    public function __construct(
        HtmlRenderer $renderer,
        RequestStack $stack,
        RouteMatchInterface $routeMatch
    ) {
        $this->renderer = $renderer;
        $this->stack = $stack;
        $this->routeMatch = $routeMatch;
    }

    public function createResponse(
        array $renderArray,
        Request $request = null,
        RouteMatchInterface $routeMatch = null
    ) {
        return $this->renderer->renderResponse(
            $renderArray,
            $request ?: $this->stack->getCurrentRequest(),
            $routeMatch ?: $this->routeMatch
        );
    }
}