<?php

namespace Ibrows\TranslationHelperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ibrows_translation_helper');
//        new \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition()->
        $rootNode->children()
            ->arrayNode('translator')->addDefaultsIfNotSet()->children()
                ->booleanNode('normalize')->defaultTrue()->end()
                ->booleanNode('create')->defaultTrue()->end()
                ->scalarNode('creator')->defaultValue('ibrows_translation_helper.defaultcreator')->end()
                ->scalarNode('decorate')->defaultValue('!!!%s')->end()
                ->arrayNode('ignoreDomains')->prototype('variable')->end()->end()
            ->end()->end()
            ->arrayNode('defaultCreator')->addDefaultsIfNotSet()->children()
                ->scalarNode('format')->defaultValue('yml')->end()
                ->scalarNode('path')->defaultValue(null)->end()
                ->scalarNode('decorate')->defaultValue('___%s')->end()
                ->booleanNode('backup')->defaultFalse()->end()
            ->end()->end()
            ->end();

        return $treeBuilder;
    }
}
