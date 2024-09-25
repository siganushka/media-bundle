<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\DependencyInjection\Compiler\AddChannelPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SiganushkaMediaBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AddChannelPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
