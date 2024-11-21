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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;

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
            $rootNode
                ->children()
                    ->scalarNode($configName)
                        ->defaultValue($entityClass)
                        ->validate()
                            ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_subclass_of($v, $entityClass, true))
                            ->thenInvalid('The value must be instanceof '.$entityClass.', %s given.')
                        ->end()
                    ->end()
                    ->scalarNode('storage')
                        ->defaultValue(LocalStorage::class)
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_subclass_of($v, StorageInterface::class, true))
                            ->thenInvalid('The value must be instanceof '.StorageInterface::class.', %s given.')
                        ->end()
                    ->end()
            ;
        }

        $this->addChannelsSection($rootNode);

        return $treeBuilder;
    }

    public function addChannelsSection(ArrayNodeDefinition $rootNode): void
    {
        /** @var ArrayNodeDefinition */
        $channelsNodeBuilder = $rootNode
            ->fixXmlConfig('channel')
            ->children()
                ->arrayNode('channels')
                    ->example([
                        'foo' => [
                            'constraint' => 'Symfony\Component\Validator\Constraints\File',
                            'constraint_options' => ['maxSize' => '2MB'],
                        ],
                        'bar' => [
                            'constraint' => 'Symfony\Component\Validator\Constraints\Image',
                            'constraint_options' => ['minWidth' => 320, 'allowSquare' => true],
                        ],
                        'baz' => [
                            'constraint' => 'App\Validator\MyCustomConstraint',
                            'constraint_options' => ['abc' => 1],
                        ],
                    ])
                    ->prototype('array')
        ;

        $channelsNodeBuilder
            ->children()
                ->scalarNode('constraint')
                    ->info('This value will be used for validation when uploading files.')
                    ->defaultValue(File::class)
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => \is_string($v) && !is_subclass_of($v, Constraint::class, true))
                        ->thenInvalid('The value must be instanceof '.Constraint::class.', %s given.')
                    ->end()
                ->end()
                ->arrayNode('constraint_options')
                    ->info('This value will be passed to the validation constraint.')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->prototype('variable')
        ;
    }
}
