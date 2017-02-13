<?php

/**
 * Implements hook_drush_cache_clear().
 */
function wmcontroller_drush_cache_clear(&$entries, $bootstrapped = false)
{
    if (!$bootstrapped) {
        return;
    }

    $entries['wmcontroller'] = 'wmcontroller_drush_flush';
}

function wmcontroller_drush_flush()
{
    \Drupal::service('wmcontroller.cache.manager')->flush();
}

