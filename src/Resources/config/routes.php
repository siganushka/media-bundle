<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\MediaBundle\SiganushkaMediaBundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $ref = new \ReflectionClass(SiganushkaMediaBundle::class);
    $path = \dirname($ref->getFileName());

    $routes->import($path.'/Controller', 'annotation');
};
