<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\wmcontroller\Service\ViewBuilder;

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
    protected function view($template = '', $data = [])
    {
        return $this->getViewBuilder()
            ->setTemplateDir($this->templateDir)
            ->setData($data)
            ->setTemplate($template);
    }

    protected function getViewBuilder(): ViewBuilder
    {
        if (!isset($this->viewBuilder)) {
            $this->viewBuilder = \Drupal::service('wmcontroller.viewbuilder');
        }

        return $this->viewBuilder;
    }
}
