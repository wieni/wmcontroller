<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ResponseBuilder implements ResponseBuilderInterface
{
    /** @var MainContentRendererInterface */
    protected $renderer;
    /** @var RequestStack */
    protected $stack;
    /** @var RouteMatchInterface */
    protected $routeMatch;

    public function __construct(
        MainContentRendererInterface $renderer,
        RequestStack $stack,
        RouteMatchInterface $routeMatch
    ) {
        $this->renderer = $renderer;
        $this->stack = $stack;
        $this->routeMatch = $routeMatch;
    }

    public function createResponse(
        array $renderArray,
        ?Request $request = null,
        ?RouteMatchInterface $routeMatch = null
    ): Response {
        return $this->renderer->renderResponse(
            $renderArray,
            $request ?: $this->stack->getCurrentRequest(),
            $routeMatch ?: $this->routeMatch
        );
    }
}
