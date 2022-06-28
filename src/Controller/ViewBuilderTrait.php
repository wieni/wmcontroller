<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\wmtwig\ViewBuilder;

trait ViewBuilderTrait
{
    /** @var ViewBuilder */
    protected $viewBuilder;
    /** @var string */
    protected $templateDir = '';

    /**
     * Return a new view from the application.
     *
     * @param  string      $template
     * @param  array       $data
     * @return ViewBuilder
     */
    protected function view(string $template = '', array $data = [])
    {
        return $this->getViewBuilder()
            ->setTemplateDir($this->templateDir)
            ->setData($data)
            ->setTemplate($template);
    }

    protected function getViewBuilder(): ViewBuilder
    {
        if (!isset($this->viewBuilder)) {
            $this->viewBuilder = \Drupal::service('wmtwig.viewbuilder');
        }

        return $this->viewBuilder;
    }
}
