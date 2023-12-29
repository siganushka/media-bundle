<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Doctrine\EventListener\MediaRemoveListener;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\FilesystemStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SiganushkaMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias(StorageInterface::class, $config['storage']);

        $mediaRemoveListenerDef = $container->findDefinition(MediaRemoveListener::class);
        $mediaRemoveListenerDef
            ->addTag('doctrine.orm.entity_listener', ['event' => 'postRemove', 'entity' => Media::class])
        ;

        $filesystemStorageDef = $container->findDefinition(FilesystemStorage::class);
        $filesystemStorageDef->setArgument('$publicDir', '%kernel.project_dir%/public');
        $filesystemStorageDef->setArgument('$uploadDir', 'uploads');

        $channelRegistryDef = $container->findDefinition(ChannelRegistry::class);
        $channelRegistryDef->setArgument('$channels', new TaggedIteratorArgument('siganushka_media.channel'));

        $container->registerForAutoconfiguration(ChannelInterface::class)
            ->addTag('siganushka_media.channel')
        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('siganushka_generic')) {
            return;
        }

        $configs = $container->getExtensionConfig($this->getAlias());

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $overrideMappings = [];
        if (Media::class !== $config['media_class']) {
            $overrideMappings[] = Media::class;
        }

        $container->prependExtensionConfig('siganushka_generic', [
            'doctrine' => ['entity_to_superclass' => $overrideMappings],
        ]);
    }
}
