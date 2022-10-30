<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\MediaBundle\SiganushkaMediaBundle;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
    ;

    $ref = new \ReflectionClass(SiganushkaMediaBundle::class);
    $services->load($ref->getNamespaceName().'\\', '../../')
        ->exclude([
            '../../DependencyInjection/',
            '../../Entity/',
            '../../Event/',
            '../../Exception/',
            '../../Resources/',
            '../../Storage/AliyunOssStorage.php',
            '../../SiganushkaMediaBundle.php',
        ]);
};
