<?php

namespace Drupal\wmcontroller\Entity;

interface HasPresenterInterface
{
    /** @return string Name of the service that implements a PresenterInterface */
    public function getPresenterService();
}
