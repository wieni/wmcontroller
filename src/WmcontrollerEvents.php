<?php

namespace Drupal\wmcontroller;

final class WmcontrollerEvents
{
    /**
     * Will be triggered when an EntityInterface is being passed
     * to a controller. (i.e.: the main entity that is supposed to be rendered)
     *
     * The event object is an instance of
     * @see \Drupal\wmcontroller\Event\MainEntityEvent
     *
     * @see Dispatcher::dispatchMainEntity().
     */
    public const MAIN_ENTITY_RENDER = 'entity.main.render';
}
