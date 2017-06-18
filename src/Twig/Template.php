<?php

namespace Drupal\wmcontroller\Twig;

use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmcontroller\Event\EntityPresentedEvent;
use Drupal\wmcontroller\Event\PresentedEvent;

abstract class Template extends \Twig_Template
{
    protected static $dispatched = [];

    protected static $dispatcher;

    protected function getDispatcher()
    {
        if (isset(static::$dispatcher)) {
            return static::$dispatcher;
        }

        return static::$dispatcher = \Drupal::service('event_dispatcher');
    }

    public function display(array $context, array $blocks = array())
    {
        if ($this->env->isDebug()) {
            print('<!-- TWIG DEBUG -->');
            printf('<!-- Template: %s -->', $this->getTemplateName());
        }

        foreach ($context as $k => $var) {
            $event = new PresentedEvent($var);
            $this->getDispatcher()->dispatch(
                WmcontrollerEvents::PRESENTED,
                $event
            );

            $var = $event->getItem();
            $context[$k] = $var;

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
