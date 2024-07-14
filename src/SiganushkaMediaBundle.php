<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SiganushkaMediaBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(ChannelInterface::class)
            ->addTag('siganushka_media.channel')
        ;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
