<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\MediaBundle\Controller\MediaController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('siganushka_media_getcollection', '/media')
        ->controller([MediaController::class, 'getCollection'])
        ->methods(['GET'])
        ->stateless(true)
    ;

    $routes->add('siganushka_media_postcollection', '/media')
        ->controller([MediaController::class, 'postCollection'])
        ->methods(['POST'])
        ->stateless(true)
    ;

    $routes->add('siganushka_media_getitem', '/media/{hash}')
        ->controller([MediaController::class, 'getItem'])
        ->methods(['GET'])
        ->stateless(true)
    ;

    $routes->add('siganushka_media_deleteitem', '/media/{hash}')
        ->controller([MediaController::class, 'deleteItem'])
        ->methods(['DELETE'])
        ->stateless(true)
    ;
};
