<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
                        ->ifTrue(function (mixed $v) {
                            if (!class_exists($v)) {
                                return false;
                            }

                            return !is_subclass_of($v, Media::class);
                        })
                        ->thenInvalid('The %s class must extends '.Media::class.' for using the "media_class".')
                    ->end()
                ->end()
                ->scalarNode('storage')
                    ->defaultValue(LocalStorage::class)
                    ->cannotBeEmpty()
                ->end()
        ;

        return $treeBuilder;
    }
}
