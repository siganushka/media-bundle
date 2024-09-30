<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection\Compiler;

use Siganushka\MediaBundle\ChannelRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ChannelPass implements CompilerPassInterface
{
    public const CHANNEL_TAG = 'siganushka_media.channel';

    public function process(ContainerBuilder $container): void
    {
        $servicesMap = [];
        foreach ($container->findTaggedServiceIds(self::CHANNEL_TAG, true) as $serviceId => $tag) {
            $alias = ChannelRegistry::normalizeAlias($serviceId);
            $servicesMap[$alias] = new Reference($serviceId);
        }

        $channelRegistryDef = $container->findDefinition(ChannelRegistry::class);
        $channelRegistryDef->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));
    }
}
