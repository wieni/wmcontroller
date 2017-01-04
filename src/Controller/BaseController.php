<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\ViewBuilder\ViewBuilder;

abstract class BaseController extends ControllerBase
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
        $builder = (new ViewBuilder())
            ->setTemplateDir($this->templateDir)
            ->setData($data)
            ->setTemplate($template);

        return $builder;
    }

}