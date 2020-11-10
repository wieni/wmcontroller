<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ViewBuilder
{
    /** @var MainContentRendererInterface */
    protected $renderer;
    /** @var RequestStack */
    protected $requestStack;
    /** @var RouteMatchInterface */
    protected $routeMatch;

    /** @var string */
    protected $templateDir;
    /** @var string */
    protected $template;
    /** @var array */
    protected $data = [];
    /** @var CacheableMetadata */
    protected $cacheabilityMetadata;

    public function __construct(
        MainContentRendererInterface $renderer,
        RequestStack $requestStack,
        RouteMatchInterface $routeMatch
    ) {
        $this->renderer = $renderer;
        $this->requestStack = $requestStack;
        $this->routeMatch = $routeMatch;
        $this->cacheabilityMetadata = new CacheableMetadata();
    }

    public function setTemplateDir(?string $templateDir): self
    {
        $this->templateDir = $templateDir;

        return $this;
    }

    public function setTemplate(?string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set the data passed to the view
     * Has to be an associative array
     *
     * When passed [myVariable => 'I am a teapot'], the view will
     * have access to the variable 'myVariable'
     *
     * @see wmcontroller_theme_set_variables
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCacheableMetadata(): CacheableMetadata
    {
        return $this->cacheabilityMetadata;
    }

    public function addCacheableDependency($dependency)
    {
        $this->cacheabilityMetadata = $this->cacheabilityMetadata
            ->merge(CacheableMetadata::createFromObject($dependency));

        return $this;
    }

    public function addCacheContexts(string $context): self
    {
        $this->cacheabilityMetadata->addCacheContexts([$context]);

        return $this;
    }

    public function addCacheTag($tag): self
    {
        $this->cacheabilityMetadata->addCacheTags([$tag]);

        return $this;
    }

    public function addCacheTags(array $tags): self
    {
        $this->cacheabilityMetadata->addCacheTags($tags);

        return $this;
    }

    public function toRenderArray(): array
    {
        $view = [];
        $view['#_data'] = $this->data;

        if ($this->template) {
            $view['#theme'] =
                ($this->templateDir ? $this->templateDir . '.' : '') .
                $this->template;
        }

        $this->cacheabilityMetadata->applyTo($view);

        return $view;
    }

    public function toResponse(): Response
    {
        return $this->renderer->renderResponse(
            $this->toRenderArray(),
            $this->requestStack->getCurrentRequest(),
            $this->routeMatch
        );
    }
}
