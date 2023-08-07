<?php

namespace Drupal\wmcontroller\Twig;

use Drupal\wmcontroller\Twig\NodeVisitor\DebugNodeVisitor;
use Drupal\wmcontroller\Twig\NodeVisitor\DispatchParameterEventNodeVisitor;
use Twig\Extension\AbstractExtension;

class Extension extends AbstractExtension
{
    public function getNodeVisitors(): array
    {
        return [
            new DebugNodeVisitor(),
            new DispatchParameterEventNodeVisitor(),
        ];
    }
}
