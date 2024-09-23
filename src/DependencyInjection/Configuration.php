<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('siganushka_media');
        /** @var ArrayNodeDefinition */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('media_class')
                    ->defaultValue(Media::class)
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => !is_a($v, Media::class, true))
                        ->thenInvalid('The value must be instanceof '.Media::class.', %s given.')
                    ->end()
                ->end()
                ->scalarNode('storage')
                    ->defaultValue(LocalStorage::class)
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => !is_a($v, StorageInterface::class, true))
                        ->thenInvalid('The value must be instanceof '.StorageInterface::class.', %s given.')
                    ->end()
                ->end()
        ;

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
                        ->ifTrue(static fn (mixed $v): bool => !is_a($v, Constraint::class, true))
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
