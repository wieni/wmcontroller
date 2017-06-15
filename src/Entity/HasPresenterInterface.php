<?php

namespace Drupal\wmcontroller\Entity;

interface HasPresenterInterface
{
    /**
     * @return string Name of the service that implements an EntityInterface
     */
    public function getPresenterService();
}
