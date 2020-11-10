<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class ControllerBase
{
    use StringTranslationTrait;
    use MainEntityTrait;
    use RedirectBuilderTrait;
    use ViewBuilderTrait;
}
