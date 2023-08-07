<?php

namespace Drupal\wmcontroller\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

class InjectEventDispatcherNode extends Node
{
    public function compile(Compiler $compiler)
    {
        parent::compile($compiler);

        $compiler->raw('
            protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher;

            protected function getDispatcher()
            {
                if (isset($this->dispatcher)) {
                    return $this->dispatcher;
                }

                return $this->dispatcher = \Drupal::service("event_dispatcher");
            }
        ');
    }
}
