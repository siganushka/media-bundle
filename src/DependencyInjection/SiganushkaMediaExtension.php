<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Doctrine\ORM\Events;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\Doctrine\MediaRemoveListener;
use Siganushka\MediaBundle\NamingStrategy;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\AssetMapper\AssetMapperInterface;
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

        foreach (Configuration::RESOURCE_MAPPING as $configName => [, $repositoryClass]) {
            $repositoryClass = $container->findDefinition($repositoryClass);
            $repositoryClass->setArgument('$entityClass', $config[$configName]);
        }

        $servicesMap = [];
        foreach ($config['rules'] as $alias => $options) {
            $id = \sprintf('siganushka_media.rule.%s', $alias);
            $servicesMap[$alias] = new Reference($id);

            $rule = $container->register($id, Rule::class)
                ->setArgument('$alias', $alias)
                ->setArgument('$constraint', $options['constraint'])
                ->setArgument('$constraintOptions', $options['constraint_options'])
                ->setArgument('$namingStrategy', $options['naming'])
            ;

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
        $ruleRegistry->setArgument('$locator', ServiceLocatorTagPass::register($container, $servicesMap));

        $migrateCommand = $container->findDefinition(MigrateCommand::class);
        $migrateCommand->setArgument('$storageDir', '%siganushka_media.storage_dir%');

        $localStorage = $container->findDefinition(LocalStorage::class);
        $localStorage->setArgument('$storageDir', '%siganushka_media.storage_dir%');

        $namingStrategy = $container->findDefinition(NamingStrategy::class);
        $namingStrategy->setArgument('$defaultNamingStrategy', $config['naming']);

        $mediaRemoveListener = $container->findDefinition(MediaRemoveListener::class);
        $mediaRemoveListener->addTag('doctrine.orm.entity_listener', ['event' => Events::postRemove, 'entity' => $config['media_class'], 'priority' => -8]);

        $container->setAlias(StorageInterface::class, $config['storage']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = array_merge(...$configs);

        $resolveTargetEntities = [];
        foreach (Configuration::RESOURCE_MAPPING as $configName => [$interface]) {
            $resolveTargetEntities[$interface] = $config[$configName] ?? null;
        }

        if (\count($rte = array_filter($resolveTargetEntities))) {
            $container->prependExtensionConfig('doctrine', [
                'orm' => ['resolve_target_entities' => $rte],
            ]);
        }

        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [__DIR__.'/../../assets/dist' => '@siganushka/media-bundle'],
                ],
            ]);
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
