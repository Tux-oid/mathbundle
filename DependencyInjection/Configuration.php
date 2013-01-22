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
                ->scalarNode('formulas_target_path')
                    ->defaultValue('/web/bundles/rlmath/images/formulas/')
                ->end()
                ->scalarNode('temporary_dir_path')
                    ->defaultValue('/web/bundles/rlmath/tmp/')
                ->end()
            ->end()
        ->end();


        return $treeBuilder;
    }
}
