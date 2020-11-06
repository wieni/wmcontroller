<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class ControllerBase
{
    use StringTranslationTrait;
    use RedirectBuilderTrait;
    use ViewBuilderTrait;
}
