<?php

namespace Drupal\wmcontroller\Path\OutboundProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

class Pager implements OutboundPathProcessorInterface
{
    public function processOutbound(
        $path,
        &$options = array(),
        Request $request = null,
        BubbleableMetadata $bubbleable_metadata = null
    ) {
        if (
            !isset($options['route'])
            || !$options['route']->hasOption('wmcontroller.pager')
        ) {
            return $path;
        }

        $page = (int) ($options['query']['page'] ?? 0);
        if (!empty($page)) {
            $path .= '/' . $page;
        }

        return $path;
    }
}

