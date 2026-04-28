<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\MediaBundle\Controller\MediaRefController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('siganushka_media_ref', '/m/{hash<[0-9a-fA-F]+>}')
        ->controller([MediaRefController::class, '__invoke'])
        ->methods(['GET'])
    ;
};
