<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class ControllerBase
{
    use MainEntityTrait;
    use RedirectBuilderTrait;
    use StringTranslationTrait;
    use ViewBuilderTrait;
}
