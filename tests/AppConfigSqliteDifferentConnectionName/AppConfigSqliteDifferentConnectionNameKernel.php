<?php

declare(strict_types=1);

namespace Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName;

use Liip\Acme\Tests\App\AppKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppConfigSqliteDifferentConnectionNameKernel extends AppKernel
{
    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return __DIR__.'/var/cache/';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        // Load the default file.
        parent::configureContainer($container, $loader);

        // Load the file with MySQL configuration
        $loader->load(__DIR__.'/config.yml');
    }
}
