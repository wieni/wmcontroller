<?php

namespace Drupal\wmcontroller\ViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;

class ViewBuilder
{
    /** @var Dispatcher */
    private $dispatcher;

    /**  @var EntityTypeManagerInterface */
    private $entityTypeManager;

    protected $viewMode = 'full';

    protected $langCode = null;

    protected $templateDir;

    protected $template;

    /** @var EntityInterface */
    protected $entity;

    protected $data = [];

    protected $hooks = [];
    
    protected $headElements= [];

    protected $cache = [
        'tags' => [],
        'contexts' => [],
    ];

    public function __construct(Dispatcher $dispatcher, EntityTypeManagerInterface $entityTypeManager)
    {
        $this->dispatcher = $dispatcher;
        $this->entityTypeManager = $entityTypeManager;
    }

    public function setTemplateDir($templateDir)
    {
        $this->templateDir = $templateDir;
        return $this;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function setEntity(EntityInterface $entity)
    {
        $this->entity = $entity;
        return $this;
    }
    
    /**
     * @param array $headElements
     * @return $this
     */
    public function setHeadElements(array $headElements) {
        $this->headElements = $headElements;
        return $this;
    }
    
    /**
     * @param array $headElement
     * @param string $key
     * @return $this
     */
    public function addHeadElement(array $headElement, $key = '')
    {
        $key = $key ?: bin2hex(random_bytes(20));
        $this->headElements[] = [$headElement, $key];
        return $this;
    }

    /**
     * Set the data passed to the view
     * Has to be an associative array
     *
     * When passed [myVariable => 'I am a teapot'], the view will
     * have access to the variable 'myVariable'
     *
     * This is done by wmcontroller_theme_set_variables
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        if ($data && !$this->isAssociativeArray($data)) {
            throw new \RuntimeException("View data has to be an associative array");
        }
        $this->data = $data;
        return $this;
    }

    public function setViewMode($viewMode)
    {
        $this->viewMode = $viewMode;
        return $this;
    }

    public function setLangCode($langCode)
    {
        $this->langCode = $langCode;
        return $this;
    }

    /**
     * @return array
     */
    public function getHooks()
    {
        return $this->hooks;
    }
    
    /**
     * @param array $hooks
     * @return $this
     */
    public function setHooks(array $hooks)
    {
        $this->hooks = $hooks;
        return $this;
    }
    
    /**
     * @param array $cache
     * @return $this
     */
    public function setCache(array $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function addCacheTag(string $tag)
    {
        $this->cache['tags'][] = $tag;
        return $this;
    }
    
    public function addCacheTags(array $tag)
    {
        $this->cache['tags'] = array_merge($this->cache['tags'], $tag);
        return $this;
    }
    
    public function addCacheContexts(string $context)
    {
        $this->cache['contexts'][] = $context;
        return $this;
    }
    
    public function setCacheMaxAge(int $context)
    {
        $this->cache['max-age'] = $context;
        return $this;
    }

    public function render()
    {
        $view = [];
        if ($this->entity) {
            $render_controller = $this->entityTypeManager->getViewBuilder($this->entity->getEntityTypeId());
            $view = $render_controller->view($this->entity, $this->viewMode, $this->langCode);
        }

        // Overwrite default template when wanted
        if ($this->template) {
            $templateDir = $this->templateDir ? $this->templateDir . '.' : '';
            $view['#theme'] = $templateDir . $this->template;
        }
    
        if (count($this->headElements) > 0) {
            if (!isset($view['#attached']['html_head'])) {
                $view['#attached']['html_head'] = [];
            }
        
            $view['#attached']['html_head'] = array_merge(
                $view['#attached']['html_head'],
                $this->headElements
            );
        }

        // Add custom hooks
        $view['#pre_render'] = array_merge(
            $view['#pre_render'] ?? [],
            $this->getHooks()
        );
        $view['#_data'] = $this->data;
        
        // Add cache tags
        if (empty($view['#cache'])) {
            $view['#cache'] = $this->cache;
        } else {
            foreach (['tags', 'contexts', 'max-age'] as $key) {
                if (isset($this->cache[$key])) {
                    $value = $this->cache[$key];
                    
                    if (is_array($this->cache[$key])) {
                        $value = array_unique(
                            array_merge(
                                $view['#cache'][$key] ?: [],
                                $this->cache[$key]
                            )
                        );
                    }
                    
                    $view['#cache'][$key] = $value;
                }
            }
        }

        if ($view['#cache']['tags']) {
            $this->dispatcher->dispatchTags($view['#cache']['tags']);
        }
        
        return $view;
    }

    private function isAssociativeArray(array $array)
    {
        if ([] === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
