<?php

declare(strict_types=1);

namespace steevanb\DevBundle\DependencyInjection;

use Symfony\Component\Config\{
    Definition\Builder\NodeBuilder,
    Definition\Builder\TreeBuilder,
    Definition\ConfigurationInterface
};

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dev');
        $rootNode = $treeBuilder->getRootNode();

        $this
            ->addTranslationConfig($rootNode->children())
            ->addValidateSchemaConfig($rootNode->children());

        return $treeBuilder;
    }

    protected function addTranslationConfig(NodeBuilder $node): self
    {
        $node
            ->arrayNode('translation_not_found')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('enabled')
                        ->defaultTrue()
                        ->validate()
                        ->ifNotInArray(array(true, false))
                            ->thenInvalid('Invalid value %s, boolean required.')
                        ->end()
                    ->end()
                    ->scalarNode('allow_fallbacks')
                        ->defaultFalse()
                        ->validate()
                        ->ifNotInArray(array(true, false))
                            ->thenInvalid('Invalid value %s, boolean required.')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $this;
    }

    protected function addValidateSchemaConfig(NodeBuilder $node): self
    {
        $node
            ->arrayNode('validate_schema')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('enabled')
                        ->defaultTrue()
                        ->validate()
                        ->ifNotInArray(array(true, false))
                            ->thenInvalid('Invalid value %s, boolean required.')
                        ->end()
                    ->end()
                    ->arrayNode('disabled_urls')
                        ->defaultValue(array('/_wdt', '/_profiler/', '/_errors'))
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('event')
                        ->defaultValue('kernel.request')
                        ->validate()
                        ->ifNotInArray(array('kernel.request', 'kernel.response'))
                            ->thenInvalid('Invalid value %s, should be kernel.request or kernel.response.')
                        ->end()
                    ->end()
                    ->arrayNode('excludes')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('paths')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('bundles')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('enabled')
                                ->defaultTrue()
                                ->validate()
                                ->ifNotInArray(array(true, false))
                                    ->thenInvalid('Invalid value %s, boolean required.')
                                ->end()
                            ->end()
                            ->arrayNode('bundles')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $this;
    }
}
