<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection\Compiler;

use Siganushka\MediaBundle\ChannelRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddChannelPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $servicesMap = [];
        foreach ($container->findTaggedServiceIds('siganushka_media.channel', true) as $serviceId => $tag) {
            $alias = ChannelRegistry::normalizeAlias($serviceId);
            $servicesMap[$alias] = new Reference($serviceId);
        }

        $channelRegistryDef = $container->findDefinition(ChannelRegistry::class);
        $channelRegistryDef->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));
    }
}
