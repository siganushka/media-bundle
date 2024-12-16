<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Doctrine\ORM\Events;
use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\Doctrine\MediaRemoveListener;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\File;

class SiganushkaMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias(StorageInterface::class, $config['storage']);

        foreach (Configuration::$resourceMapping as $configName => [, $repositoryClass]) {
            $repository = $container->findDefinition($repositoryClass);
            $repository->setArgument('$entityClass', $config[$configName]);
        }

        $servicesMap = [];

        $config['channels'] += ['default' => ['constraint' => File::class]];
        foreach ($config['channels'] as $alias => $options) {
            $id = \sprintf('siganushka_media.channels.%s', $alias);
            $servicesMap[$alias] = new Reference($id);

            $container->register($id, Channel::class)
                ->setArgument('$alias', $alias)
                ->setArgument('$options', $options)
            ;
        }

        $channelRegistry = $container->findDefinition(ChannelRegistry::class);
        $channelRegistry->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));

        $publicDirectory = $this->getPublicDirectory($container);
        $migrateCommand = $container->findDefinition(MigrateCommand::class);
        $migrateCommand->setArgument('$publicDir', $publicDirectory);

        $localStorage = $container->findDefinition(LocalStorage::class);
        $localStorage->setArgument('$publicDir', $publicDirectory);
        $localStorage->setArgument('$uploadDir', 'uploads');

        $mediaRemoveListener = $container->findDefinition(MediaRemoveListener::class);
        $mediaRemoveListener->addTag('doctrine.orm.entity_listener', ['event' => Events::postRemove, 'entity' => $config['media_class']]);
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

        // @see https://symfony.com/doc/current/frontend/create_ux_bundle.html#specifics-for-asset-mapper
        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@siganushka/media-bundle',
                    ],
                ],
            ]);
        }
    }

    /**
     * @see https://symfony.com/doc/current/configuration/override_dir_structure.html#override-the-public-directory
     * @see https://github.com/symfony/framework-bundle/blob/7.1/DependencyInjection/FrameworkExtension.php#L3129
     */
    private function getPublicDirectory(ContainerBuilder $container): string
    {
        /** @var string */
        $projectDir = $container->getParameter('kernel.project_dir');
        $defaultPublicDir = $projectDir.'/public';

        $composerFilePath = $projectDir.'/composer.json';
        if (!file_exists($composerFilePath)) {
            return $defaultPublicDir;
        }

        /** @var array */
        $composerConfig = json_decode((new Filesystem())->readFile($composerFilePath), true, flags: \JSON_THROW_ON_ERROR);

        return isset($composerConfig['extra']['public-dir']) ? $projectDir.'/'.$composerConfig['extra']['public-dir'] : $defaultPublicDir;
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        /** @var array */
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
