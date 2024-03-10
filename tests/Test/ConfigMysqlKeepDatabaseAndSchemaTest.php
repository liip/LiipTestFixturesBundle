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

namespace Liip\Acme\Tests\Test;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\Acme\Tests\AppConfigMysqlKeepDatabaseAndSchema\AppConfigMysqlKernelKeepDatabaseAndSchema;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Test MySQL database while keeping the database and schema.
 *
 * The following tests require a connection to a MySQL database.
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigMysql/config.yml file.
 *
 * Use Tests/AppConfigMysqlKeepDatabaseAndSchema/AppConfigMysqlKernelKeepDatabaseAndSchema.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigMysqlKeepDatabaseAndSchemaTest extends ConfigMysqlTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createDatabaseAndSchema();
    }

    protected static function getKernelClass(): string
    {
        return AppConfigMysqlKernelKeepDatabaseAndSchema::class;
    }

    private function createDatabaseAndSchema()
    {
        $doctrine = $this->getTestContainer()->get('doctrine');

        $connection = $doctrine->getConnection();

        $params = $connection->getParams();

        $name = $params['dbname'];

        unset($params['dbname'], $params['path'], $params['url']);

        $tmpConnection = DriverManager::getConnection($params);

        $schemaManager = $tmpConnection->createSchemaManager();

        try {
            $schemaManager->createDatabase($name);
        } catch (\Exception $e) {
        }

        $doctrineManager = $doctrine->getManager();

        $schemaTool = new SchemaTool($doctrineManager);

        try {
            $schemaTool->createSchema($doctrineManager->getMetadataFactory()->getAllMetadata());
        } catch (\Exception $e) {
        }
    }
}
