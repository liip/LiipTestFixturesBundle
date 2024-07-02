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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class LiipTestFixturesExtension extends Extension
{
    /**
     * Loads the services based on your application configuration.
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('database_tools.xml');

        foreach ($config as $key => $value) {
            // If the node is an array,
            // e.g. "liip_test_fixtures.cache_db.mysql",
            // set the value as
            // "liip_test_fixtures.cache_db.mysql"
            // instead of an array "liip_test_fixtures.cache_db"
            // with a "mysql" key.
            if (\is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $container->setParameter($this->getAlias().'.'.$key.
                        '.'.$key2, $value2);
                }
            } else {
                $container->setParameter($this->getAlias().'.'.$key, $value);
            }
        }
    }
}
