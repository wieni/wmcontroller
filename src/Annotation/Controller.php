<?php

namespace Drupal\wmcontroller\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class Controller extends Plugin
{
    /** @var string */
    public $entity_type;
    /** @var string */
    public $bundle;

    public function getId()
    {
        if (isset($this->definition['entity_type'], $this->definition['bundle'])) {
            return implode('.', [
                $this->definition['entity_type'],
                $this->definition['bundle'],
            ]);
        }

        if (isset($this->definition['entity_type'])) {
            return implode('.', [
                $this->definition['entity_type'],
                $this->definition['entity_type'],
            ]);
        }

        return parent::getId();
    }
}
