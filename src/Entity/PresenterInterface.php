<?php

namespace Drupal\wmcontroller\Entity;

interface PresenterInterface
{
    public function setEntity($entity);

    /**
     * @return EntityInterface
     */
    public function getEntity();
}
