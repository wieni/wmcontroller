<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Controller\ControllerBase as DrupalControllerBase;
use Drupal\wmcontroller\ViewBuilder\ViewBuilder;

abstract class ControllerBase extends DrupalControllerBase
{

    protected $templateDir = '';

    /**
     * Return a new view from the application.
     *
     * @param string $template
     * @param array $data
     * @return ViewBuilder
     */
    protected function view($template = '', $data = [])
    {
        $builder = $this->getNewViewBuilderInstance()
            ->setTemplateDir($this->templateDir)
            ->setData($data)
            ->setTemplate($template);

        return $builder;
    }

    /**
     * @return ViewBuilder
     */
    private function getNewViewBuilderInstance()
    {
        return \Drupal::service('wmcontroller.viewbuilder');
    }

}