<?php

namespace Drupal\wmcontroller\Twig\NodeVisitor;

use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * A Twig node visitor that adds debug information around components.
 */
class DebugNodeVisitor implements NodeVisitorInterface
{
    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if (!$env->isDebug()) {
            return $node;
        }

        if (!$node instanceof ModuleNode) {
            return $node;
        }

        if (!$source = $node->getSourceContext()) {
            return $node;
        }

        $name = $source->getName();
        $path = str_replace(DRUPAL_ROOT . '/', '', $source->getPath());

        if ($this->isNodeEmpty($node->getNode('body'))) {
            // This node is empty.
            return $node;
        }

        $nodes = [
            new TextNode('<!-- TWIG DEBUG -->', 0),
            new TextNode(sprintf('<!-- Template: %s -->', $name), 0),
        ];

        if ($name !== $path) {
            $nodes[] = new TextNode(sprintf('<!-- Path: %s -->', $path), 0);
        }

        $node->getNode('display_start')
            ->setNode('_components_debug', new Node($nodes));

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
