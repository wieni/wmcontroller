<?php

namespace Drupal\wmcontroller\Service;

interface TemplateLocatorInterface
{
    /**
     * Get all custom themes
     *
     * @return array
     */
    public function getThemes(): array;
}
