<?php

namespace Drupal\wmcontroller\ViewBuilder;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;
use Drupal\wmcontroller\Service\Cache\Dispatcher;
use Drupal\wmcontroller\Service\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class ViewBuilder implements AttachmentsInterface, CacheableResponseInterface
{
    use AttachmentsTrait;
    use CacheableResponseTrait;

    /** @var Dispatcher */
    protected $dispatcher;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var ResponseBuilder */
    protected $responseBuilder;

    /** @var string */
    protected $viewMode = 'full';
    /** @var string|null */
    protected $langCode;
    /** @var string|null */
    protected $templateDir;
    /** @var string|null */
    protected $template;
    /** @var EntityInterface */
    protected $entity;
    /** @var array */
    protected $data = [];
    /** @var array[] */
    protected $cache = [
        'tags' => [],
        'contexts' => [],
    ];

    public function __construct(
        Dispatcher $dispatcher,
        EntityTypeManagerInterface $entityTypeManager,
        ResponseBuilder $responseBuilder
    ) {
        $this->dispatcher = $dispatcher;
        $this->entityTypeManager = $entityTypeManager;
        $this->responseBuilder = $responseBuilder;
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

    public function setEntity(EntityInterface $entity): self
    {
        $this->entity = $entity;
        $this->dispatcher->dispatchMainEntity($entity);

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

    public function setViewMode(string $viewMode): self
    {
        $this->viewMode = $viewMode;

        return $this;
    }

    public function setLangCode(?string $langCode): self
    {
        $this->langCode = $langCode;

        return $this;
    }

    public function setCache(array $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function addCacheTag($tag): self
    {
        if ($tag instanceof EntityInterface) {
            return $this->addCacheTags($tag->getCacheTagsToInvalidate());
        }
        
        if ($tag) {
            $this->cache['tags'][] = $tag;
        }

        return $this;
    }

    public function addCacheTags(array $tags): self
    {
        array_walk($tags, [$this, 'addCacheTag']);

        return $this;
    }

    public function addCacheContexts(string $context): self
    {
        $this->cache['contexts'][] = $context;

        return $this;
    }

    public function toRenderArray(): array
    {
        $view = [];

        if ($this->entity) {
            $view = $this->createOriginalRenderArrayFromEntity($this->entity);
        }

        $view['#_data'] = $this->data;
        $view['#attached'] = $this->attachments;
        $this->getCacheableMetadata()->applyTo($view);
        $this->addThemeToRenderArray($view);
        $this->addCacheTagsToRenderArray($view);
        $this->dispatchCacheTags($view);
        $this->dispatchCacheTagsOfPassedEntities($view);

        return $view;
    }

    public function toResponse(): Response
    {
        return $this->responseBuilder->createResponse($this->toRenderArray());
    }

    protected function createOriginalRenderArrayFromEntity(EntityInterface $entity): array
    {
        $renderController = $this->entityTypeManager->getViewBuilder(
            $entity->getEntityTypeId()
        );

        return $renderController->view(
            $entity,
            $this->viewMode,
            $this->langCode
        );
    }

    protected function addThemeToRenderArray(array &$view): void
    {
        if ($this->template) {
            $view['#theme'] =
                ($this->templateDir ? $this->templateDir . '.' : '') .
                $this->template;
        }
    }

    protected function addCacheTagsToRenderArray(array &$view): void
    {
        // Add cache tags
        if (empty($view['#cache'])) {
            $view['#cache'] = $this->cache;
            return;
        }

        foreach (['tags', 'contexts', 'max-age'] as $key) {
            if (!isset($this->cache[$key])) {
                continue;
            }

            if (!is_array($this->cache[$key])) {
                $view['#cache'][$key] = $this->cache[$key];
                continue;
            }

            $view['#cache'] += [$key => []];
            $view['#cache'][$key] = array_unique(
                array_merge(
                    $view['#cache'][$key],
                    $this->cache[$key]
                )
            );
        }
    }

    protected function dispatchCacheTags(array $view): void
    {
        if ($view['#cache']['tags']) {
            $this->dispatcher->dispatchTags($view['#cache']['tags']);
        }
    }

    protected function dispatchCacheTagsOfPassedEntities(array $view): void
    {
        foreach ($view['#_data'] as $entity) {
            if ($entity instanceof EntityInterface) {
                $this->dispatcher->dispatchPresented($entity);
            }
        }
    }
}
