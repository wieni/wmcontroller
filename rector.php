<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
    ]);

    $parameters->set(Option::AUTOLOAD_PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/vendor/drupal',
        __DIR__ . '/vendor/wieni',
    ]);

    $containerConfigurator->import(__DIR__ . '/vendor/wieni/wmcodestyle/rector/drupal-module.php');
};
