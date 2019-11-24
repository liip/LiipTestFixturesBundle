<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\TestFixturesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('liip_test_fixtures');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('liip_test_fixtures', 'array');
        }

        $rootNode
            ->children()
                ->arrayNode('cache_db')
                    ->addDefaultsIfNotSet()
                    ->ignoreExtraKeys(false)
                        ->children()
                            ->scalarNode('sqlite')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->booleanNode('keep_database_and_schema')->defaultFalse()->end()
            ->booleanNode('cache_metadata')->defaultTrue()->end()
        ;

        return $treeBuilder;
    }
}
