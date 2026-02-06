<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Video;

class Configuration implements ConfigurationInterface
{
    public static array $resourceMapping = [
        'media_class' => [Media::class, MediaRepository::class],
    ];

    /**
     * @return TreeBuilder<'array'>
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('siganushka_media');
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
        $this->addRulesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition<NodeParentInterface> $rootNode
     */
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

    /**
     * @param ArrayNodeDefinition<NodeParentInterface> $rootNode
     */
    public function addRulesSection(ArrayNodeDefinition $rootNode): void
    {
        $ruleNode = $rootNode
            ->fixXmlConfig('rule')
            ->children()
                ->arrayNode('rules')
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
                            'constraint' => 'video',
                            'constraint_options' => ['minWidth' => 1280, 'minHeight' => 720, 'allowedContainers' => ['mp4', 'webm']],
                        ],
                        'custom_rule' => [
                            'constraint' => 'App\Validator\MyCustomConstraint',
                            'constraint_options' => ['abc' => 1],
                        ],
                    ])
                    ->prototype('array')
        ;

        $ruleNode
            ->children()
                ->scalarNode('constraint')
                    ->info('This value will be used for validation when uploading files.')
                    ->cannotBeEmpty()
                    ->defaultValue(File::class)
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn (string $v): string => match ($v) {
                            'file' => File::class,
                            'image' => Image::class,
                            'video' => Video::class,
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
                ->scalarNode('naming')
                    ->info('This value defines the file naming strategy.')
                    ->defaultNull()
                ->end()
                ->arrayNode('resize')
                    ->info('This value is used when resizing the image.')
                    ->canBeEnabled()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v) => \is_int($v))
                        ->then(static fn (int $v): array => ['max_width' => $v, 'max_height' => $v, 'enabled' => true])
                    ->end()
                    ->children()
                        ->integerNode('max_width')->defaultValue(1920)->end()
                        ->integerNode('max_height')->defaultValue(7680)->end()
                    ->end()
                ->end()
                ->arrayNode('optimize')
                    ->info('This value is used to optimize the image quality.')
                    ->canBeEnabled()
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v) => \is_int($v))
                        ->then(static fn (int $v): array => ['quality' => $v, 'enabled' => true])
                    ->end()
                    ->children()
                        ->integerNode('quality')->defaultValue(90)->min(1)->max(100)->end()
                    ->end()
                ->end()
        ;
    }
}
