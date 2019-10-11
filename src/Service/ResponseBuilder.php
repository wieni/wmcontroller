<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Render\MainContent\HtmlRenderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ResponseBuilder implements ResponseBuilderInterface
{
    /** @var \Drupal\Core\Render\MainContent\HtmlRenderer */
    protected $renderer;
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $stack;
    /** @var \Drupal\Core\Routing\RouteMatchInterface */
    protected $routeMatch;

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