<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\DependencyInjection\Compiler\ChannelPass;
use Siganushka\MediaBundle\Doctrine\EventListener\MediaRemoveListener;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SiganushkaMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach (Configuration::$resourceMapping as $configName => [, $repositoryClass]) {
            $repositoryDef = $container->findDefinition($repositoryClass);
            $repositoryDef->setArgument('$entityClass', $config[$configName]);
        }

        $container->setAlias(StorageInterface::class, $config['storage']);

        $mediaRemoveListenerDef = $container->findDefinition(MediaRemoveListener::class);
        $mediaRemoveListenerDef->addTag('doctrine.orm.entity_listener', ['event' => 'postRemove', 'entity' => $config['media_class']]);

        $migrateCommandDef = $container->findDefinition(MigrateCommand::class);
        $migrateCommandDef->setArgument('$publicDir', '%kernel.project_dir%/public');

        $localStorageDef = $container->findDefinition(LocalStorage::class);
        $localStorageDef->setArgument('$publicDir', '%kernel.project_dir%/public');
        $localStorageDef->setArgument('$uploadDir', 'uploads');

        $container->registerForAutoconfiguration(ChannelInterface::class)
            ->addTag(ChannelPass::CHANNEL_TAG)
        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $mappingOverride = [];
        foreach (Configuration::$resourceMapping as $configName => [$entityClass]) {
            if ($config[$configName] !== $entityClass) {
                $mappingOverride[$entityClass] = $config[$configName];
            }
        }

        $container->prependExtensionConfig('siganushka_generic', [
            'doctrine' => ['mapping_override' => $mappingOverride],
        ]);
    }
}
