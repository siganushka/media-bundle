<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Doctrine\ORM\Events;
use Siganushka\GenericBundle\DependencyInjection\SiganushkaGenericExtension;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\Doctrine\MediaListener;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
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
        foreach ($config['rules'] as $alias => $options) {
            $id = \sprintf('siganushka_media.rule.%s', $alias);
            $servicesMap[$alias] = new Reference($id);

            $rule = $container->register($id, Rule::class)
                ->setArgument('$alias', $alias)
                ->setArgument('$constraint', $options['constraint'])
                ->setArgument('$constraintOptions', $options['constraint_options'])
            ;

            if ($options['reserve_client_name']) {
                $rule->setArgument('$reserveClientName', $options['reserve_client_name']);
            }

            if ($this->isConfigEnabled($container, $options['resize'])) {
                if (!class_exists(\Imagick::class)) {
                    throw new \LogicException('Media resize support cannot be enabled as the resize component is not installed. Try enabling the imagick extension.');
                }

                $rule->setArgument('$resizeToMaxWidth', $options['resize']['max_width']);
                $rule->setArgument('$resizeToMaxHeight', $options['resize']['max_height']);
            }

            if ($this->isConfigEnabled($container, $options['optimize'])) {
                if (!class_exists(OptimizerChainFactory::class)) {
                    throw new \LogicException('Media optimize support cannot be enabled as the optimize component is not installed. Try running "composer require spatie/image-optimizer".');
                }

                $rule->setArgument('$optimizeToQuality', $options['optimize']['quality']);
            }
        }

        $ruleRegistry = $container->findDefinition(RuleRegistry::class);
        $ruleRegistry->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));

        $migrateCommand = $container->findDefinition(MigrateCommand::class);
        $migrateCommand->setArgument('$storageDir', '%siganushka_media.storage_dir');

        $localStorage = $container->findDefinition(LocalStorage::class);
        $localStorage->setArgument('$storageDir', '%siganushka_media.storage_dir');

        $mediaListener = $container->findDefinition(MediaListener::class);
        $mediaListener->addTag('doctrine.orm.entity_listener', ['event' => Events::postRemove, 'entity' => $config['media_class']]);

        $container->setAlias(StorageInterface::class, $config['storage']);
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
