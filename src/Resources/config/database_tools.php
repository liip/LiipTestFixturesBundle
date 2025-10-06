<?php

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Liip\TestFixturesBundle\Services\DatabaseBackup\MongodbDatabaseBackup;
use Liip\TestFixturesBundle\Services\DatabaseBackup\MysqlDatabaseBackup;
use Liip\TestFixturesBundle\Services\DatabaseBackup\PgsqlDatabaseBackup;
use Liip\TestFixturesBundle\Services\DatabaseBackup\SqliteDatabaseBackup;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\MongoDBDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMSqliteDatabaseTool;
use Liip\TestFixturesBundle\Services\FixturesLoaderFactory;
use Liip\TestFixturesBundle\Services\MongoDBFixturesLoaderFactory;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(FixturesLoaderFactory::class)
            ->public()
            ->args([
                service('doctrine.fixtures.loader')->nullOnInvalid(),
            ])

        ->set(MongoDBFixturesLoaderFactory::class)
            ->public()
            ->args([
                service('doctrine_mongodb.odm.symfony.fixtures.loader')->nullOnInvalid(),
            ])

        ->set(SqliteDatabaseBackup::class)
            ->public()
            ->args([
                service('service_container'),
                service(FixturesLoaderFactory::class),
            ])

        ->set(MysqlDatabaseBackup::class)
            ->public()
            ->args([
                service('service_container'),
                service(FixturesLoaderFactory::class),
            ])

        ->set(PgsqlDatabaseBackup::class)
            ->public()
            ->args([
                service('service_container'),
                service(FixturesLoaderFactory::class),
            ])

        ->set(MongodbDatabaseBackup::class)
            ->public()
            ->args([
                service('service_container'),
                service(MongoDBFixturesLoaderFactory::class),
            ])

        ->set(ORMDatabaseTool::class)
            ->args([
                service('service_container'),
                service(FixturesLoaderFactory::class),
            ])

        ->set(ORMSqliteDatabaseTool::class)
            ->args([
                service('service_container'),
                service(FixturesLoaderFactory::class),
            ])

        ->set(MongoDBDatabaseTool::class)
            ->args([
                service('service_container'),
                service(MongoDBFixturesLoaderFactory::class),
            ])

        ->set(DatabaseToolCollection::class)
            ->public()
            ->args([
                service('service_container'),
                null, // deprecated argument
            ])
            ->call('add', [service(ORMDatabaseTool::class)])
            ->call('add', [service(ORMSqliteDatabaseTool::class)])
            ->call('add', [service(MongoDBDatabaseTool::class)])
    ;
};
