<?php

namespace Drupal\wmcontroller\Entity;

interface PresenterInterface
{
    public function setEntity($entity);

    public function getEntity();
}
