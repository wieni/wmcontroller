<?php

namespace Drupal\wmcontroller\Twig;

use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmcontroller\Event\EntityPresentedEvent;

abstract class Template extends \Twig_Template
{
    protected static $dispatched = [];

    public function display(array $context, array $blocks = array())
    {
        if ($this->env->isDebug()) {
            print('<!-- TWIG DEBUG -->');
            printf('<!-- Template: %s -->', $this->getTemplateName());
        }

        foreach ($context as $var) {
            if (!($var instanceof \Drupal\Core\Entity\EntityInterface)) {
                continue;
            }

            $key = sprintf('%s:%s', $var->getEntityTypeId(), $var->id());
            if (isset(self::$dispatched[$key])) {
                continue;
            }

            self::$dispatched[$key] = true;
            \Drupal::service('wmcontroller.cache.dispatcher')
                ->dispatchPresented($var);
        }

        return parent::display($context, $blocks);
    }
}

