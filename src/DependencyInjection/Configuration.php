<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class Configuration implements ConfigurationInterface
{
    public static array $resourceMapping = [
        'media_class' => [Media::class, MediaRepository::class],
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('siganushka_media');
        /** @var ArrayNodeDefinition */
        $rootNode = $treeBuilder->getRootNode();

        foreach (static::$resourceMapping as $configName => [$entityClass]) {
            $rootNode->children()
                ->scalarNode($configName)
                    ->defaultValue($entityClass)
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_a($v, $entityClass, true))
                        ->thenInvalid('The value must be instanceof '.$entityClass.', %s given.')
                    ->end()
                ->end()
            ;
        }

        $this->addStorageSection($rootNode);
        $this->addChannelsSection($rootNode);

        return $treeBuilder;
    }

    public function addStorageSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->scalarNode('storage')
            ->defaultValue(LocalStorage::class)
            ->cannotBeEmpty()
            ->validate()
                ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_a($v, StorageInterface::class, true))
                ->thenInvalid('The value must be instanceof '.StorageInterface::class.', %s given.')
            ->end()
        ;
    }

    public function addChannelsSection(ArrayNodeDefinition $rootNode): void
    {
        /** @var ArrayNodeDefinition */
        $channelNode = $rootNode
            ->fixXmlConfig('channel')
            ->children()
                ->arrayNode('channels')
                    ->example([
                        'foo' => [
                            'constraint' => 'file',
                            'constraint_options' => ['maxSize' => '2MB'],
                        ],
                        'bar' => [
                            'constraint' => 'image',
                            'constraint_options' => ['minWidth' => 320, 'allowSquare' => true],
                        ],
                        'baz' => [
                            'constraint' => 'App\Validator\MyCustomConstraint',
                            'constraint_options' => ['abc' => 1],
                        ],
                    ])
                    ->prototype('array')
        ;

        $channelNode
            ->children()
                ->scalarNode('constraint')
                    ->info('This value will be used for validation when uploading files.')
                    ->cannotBeEmpty()
                    ->defaultValue(File::class)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(fn (string $v): string => match ($v) {
                            'file' => File::class,
                            'image' => Image::class,
                            default => $v,
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_a($v, File::class, true))
                        ->thenInvalid('The value must be instanceof '.File::class.', %s given.')
                    ->end()
                ->end()
                ->arrayNode('constraint_options')
                    ->info('This value will be passed to the validation constraint.')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('resize')
                    ->info('This value is used when resizing the image.')
                    ->canBeEnabled()
                    ->beforeNormalization()
                        ->ifTrue(fn (mixed $v) => \is_int($v))
                        ->then(fn (int $v): array => ['max_width' => $v, 'max_height' => $v, 'enabled' => true])
                    ->end()
                    ->children()
                        ->integerNode('max_width')->end()
                        ->integerNode('max_height')->end()
                    ->end()
                ->end()
                ->arrayNode('optimize')
                    ->info('This value is used to optimize the image quality.')
                    ->canBeEnabled()
                    ->beforeNormalization()
                        ->ifTrue(fn (mixed $v) => \is_int($v))
                        ->then(fn (int $v): array => ['quality' => $v, 'enabled' => true])
                    ->end()
                    ->children()
                        ->integerNode('quality')->end()
                    ->end()
                ->end()
        ;
    }
}
