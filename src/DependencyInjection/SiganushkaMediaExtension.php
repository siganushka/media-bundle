<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Doctrine\ORM\Events;
use Siganushka\GenericBundle\DependencyInjection\SiganushkaGenericExtension;
use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\Doctrine\MediaListener;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class SiganushkaMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach (Configuration::$resourceMapping as $configName => [, $repositoryClass]) {
            $repository = $container->findDefinition($repositoryClass);
            $repository->setArgument('$entityClass', $config[$configName]);
        }

        $servicesMap = [];
        foreach ($config['channels'] as $alias => $options) {
            $id = \sprintf('siganushka_media.channel.%s', $alias);
            $servicesMap[$alias] = new Reference($id);

            $channel = $container->register($id, Channel::class)
                ->setArgument('$alias', $alias)
                ->setArgument('$constraint', $options['constraint'])
                ->setArgument('$constraintOptions', $options['constraint_options'])
            ;

            if ($options['reserve_client_name']) {
                $channel->setArgument('$reserveClientName', $options['reserve_client_name']);
            }

            if ($this->isConfigEnabled($container, $options['resize'])) {
                $channel->setArgument('$resizeToMaxWidth', $options['resize']['max_width']);
                $channel->setArgument('$resizeToMaxHeight', $options['resize']['max_height']);
            }

            if ($this->isConfigEnabled($container, $options['optimize'])) {
                if (!class_exists(OptimizerChainFactory::class)) {
                    throw new \LogicException('Media optimize support cannot be enabled as the optimize component is not installed. Try running "composer require spatie/image-optimizer".');
                }

                $channel->setArgument('$optimizeToQuality', $options['optimize']['quality']);
            }
        }

        $container->setAlias(StorageInterface::class, $config['storage']);

        $channelRegistry = $container->findDefinition(ChannelRegistry::class);
        $channelRegistry->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));

        $migrateCommand = $container->findDefinition(MigrateCommand::class);
        $migrateCommand->setArgument('$publicDir', '%kernel.project_dir%/public');

        $localStorage = $container->findDefinition(LocalStorage::class);
        $localStorage->setArgument('$publicDir', '%kernel.project_dir%/public');
        $localStorage->setArgument('$uploadDir', 'uploads');

        $mediaListener = $container->findDefinition(MediaListener::class);
        $mediaListener->addTag('doctrine.orm.entity_listener', [
            'event' => Events::postRemove,
            'entity' => $config['media_class'],
        ]);
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

        if (SiganushkaGenericExtension::isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@siganushka/media-bundle',
                    ],
                ],
            ]);
        }
    }
}
