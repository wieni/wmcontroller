<?php

namespace Drupal\wmcontroller\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

class DispatchParameterEventNode extends Node
{
    public function compile(Compiler $compiler)
    {
        parent::compile($compiler);

        $compiler->raw('
            foreach ($context as $key => $value) {
                $event = new \Drupal\wmcontroller\Event\PresentedEvent($value);
                $this->getDispatcher()->dispatch(
                    $event,
                    \Drupal\wmcontroller\WmcontrollerEvents::PRESENTED
                );

                $context[$key] = $event->getItem();
            }
        ');
    }
}
