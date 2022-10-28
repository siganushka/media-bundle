<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('siganushka_media');
        /** @var ArrayNodeDefinition */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('storage')
                    ->defaultValue('siganushka_media.storage.filesystem')
                    ->cannotBeEmpty()
                ->end()
        ;

        return $treeBuilder;
    }
}
