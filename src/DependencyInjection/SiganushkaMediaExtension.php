<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Storage\FilesystemStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SiganushkaMediaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias(StorageInterface::class, $config['storage']);

        $filesystemStorageDef = $container->findDefinition(FilesystemStorage::class);
        $filesystemStorageDef->setArgument('$publicDir', '%kernel.project_dir%/public');

        $channelRegistryDef = $container->findDefinition(ChannelRegistry::class);
        $channelRegistryDef->setArgument('$channels', new TaggedIteratorArgument('siganushka_media.channel'));

        $container->registerForAutoconfiguration(ChannelInterface::class)
            ->addTag('siganushka_media.channel')
        ;
    }
}
