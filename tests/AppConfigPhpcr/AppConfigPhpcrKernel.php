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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class AppConfigPhpcrKernel extends AppConfigSqliteKernel
{
    public function registerBundles(): array
    {
        $bundles = [];

        if (class_exists(DoctrinePHPCRBundle::class)) {
            $bundles = [
                new DoctrinePHPCRBundle(),
            ];
        }

        return array_merge(
            parent::registerBundles(),
            $bundles
        );
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // Load the default file.
        parent::configureContainer($container);

        // Load the file with PHPCR configuration
        if (class_exists(DoctrinePHPCRBundle::class)) {
            $container->import(__DIR__.'/config.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return __DIR__.'/var/cache/';
    }
}
