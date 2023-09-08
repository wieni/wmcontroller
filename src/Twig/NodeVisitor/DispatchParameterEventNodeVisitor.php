<?php

namespace Drupal\wmcontroller\Twig\NodeVisitor;

use Drupal\wmcontroller\Twig\Node\DispatchParameterEventNode;
use Drupal\wmcontroller\Twig\Node\InjectEventDispatcherNode;
use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

class DispatchParameterEventNodeVisitor implements NodeVisitorInterface
{
    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if (!$node instanceof ModuleNode) {
            return $node;
        }

        if ($this->isNodeEmpty($node->getNode('body'))) {
            // This node is empty.
            return $node;
        }

        $node->getNode('display_start')
            ->setNode('_wmcontroller_template_parameter_event_dispatch', new DispatchParameterEventNode());

        $node->getNode('class_end')
            ->setNode('_wmcontroller_template_parameter_event_dispatch', new InjectEventDispatcherNode());

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Checks whether a node - or one of its subnodes - actually contain something.
     */
    protected function isNodeEmpty(Node $node): bool
    {
        $subNodes = iterator_to_array($node);
        if (count($subNodes) > 1) {
            return FALSE;
        }

        foreach ($subNodes as $subNode) {
            if (!$this->isNodeEmpty($subNode)) {
                return FALSE;
            }
        }

        return TRUE;
    }
}
