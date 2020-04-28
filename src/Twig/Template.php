<?php

namespace Drupal\wmcontroller\Twig;

use Drupal\wmcontroller\Event\PresentedEvent;
use Drupal\wmcontroller\WmcontrollerEvents;

abstract class Template extends \Twig_Template
{
    protected static $dispatched = [];

    protected static $dispatcher;

    public function display(array $context, array $blocks = [])
    {
        if ($this->env->isDebug()) {
            $source = $this->getSourceContext();
            $name = $source->getName();
            $path = str_replace(DRUPAL_ROOT . '/', '', $source->getPath());

            echo '<!-- TWIG DEBUG -->';
            printf('<!-- Template: %s -->', $name);

            if ($name !== $path) {
                printf('<!-- Path: %s -->', $path);
            }
        }

        foreach ($context as $k => $var) {
            $event = new PresentedEvent($var);
            $this->getDispatcher()->dispatch(
                WmcontrollerEvents::PRESENTED,
                $event
            );

            $context[$k] = $event->getItem();

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

    protected function getDispatcher()
    {
        if (isset(static::$dispatcher)) {
            return static::$dispatcher;
        }

        return static::$dispatcher = \Drupal::service('event_dispatcher');
    }
}
