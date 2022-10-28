<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\MediaBundle\Controller\MediaController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('siganushka_media_getcollection', '/media')
        ->controller([MediaController::class, 'getCollection'])
        ->methods(['GET'])
    ;

    $routes->add('siganushka_media_postcollection', '/media')
        ->controller([MediaController::class, 'postCollection'])
        ->methods(['POST'])
    ;

    $routes->add('siganushka_media_getitem', '/media/{id}')
        ->controller([MediaController::class, 'getItem'])
        ->methods(['GET'])
        ->requirements(['id' => '\d+'])
    ;

    // $routes->add('siganushka_media_deleteitem', '/media/{id}')
    //     ->controller([MediaController::class, 'deleteItem'])
    //     ->methods(['DELETE'])
    //     ->requirements(['id' => '\d+'])
    // ;
};
