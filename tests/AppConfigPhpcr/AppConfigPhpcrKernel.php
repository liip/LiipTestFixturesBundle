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

namespace Liip\Acme\Tests\AppConfigPhpcr;

use Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle;
use Liip\Acme\Tests\AppConfigSqlite\AppConfigSqliteKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppConfigPhpcrKernel extends AppConfigSqliteKernel
{
    public function registerBundles(): array
    {
        $bundles = [];

        if (class_exists(DoctrinePHPCRBundle::class)) {
            $bundles = [
                new \Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            ];
        }

        return array_merge(
            parent::registerBundles(),
            $bundles
        );
    }

    /**
     * Load the config.yml from the current directory.
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        // Load the default file.
        parent::registerContainerConfiguration($loader);

        // Load the file with PHPCR configuration
        if (class_exists(DoctrinePHPCRBundle::class)) {
            $loader->load(__DIR__ . '/config.yml');
        }
    }
}
