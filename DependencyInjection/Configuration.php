<?php

namespace RL\MathBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('rl_math');

        $rootNode
            ->children()
                ->booleanNode('use_latex')
                    ->defaultFalse()
                ->end()
                ->arrayNode('php_math_publisher')
                    ->addDefaultsIfNotSet()
                    ->children()
                    ->scalarNode('formulas_target_path')
                        ->defaultValue('/web/bundles/rlmain/images/formulas/')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();


        return $treeBuilder;
    }
}
