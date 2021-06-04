<?php

function hook_wmcontroller_controller_info_alter(array &$definitions)
{
    $definitions['node.page']['class'] = \Drupal\my_module\Controller\Node\PageController::class;
}
