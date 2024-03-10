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

use Liip\Acme\Tests\AppConfigPgsql\AppConfigPgsqlKernel;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Test PostgreSQL database.
 *
 * The following tests require a connection to a PostgreSQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the PostgreSQL connection
 * parameters in the Tests/AppConfigPgsql/config.yml file.
 *
 * Use Tests/AppConfigPgsql/AppConfigPgsqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigPgsqlTest extends ConfigMysqlTest
{
    public function testToolType(): void
    {
        $this->assertInstanceOf(ORMDatabaseTool::class, $this->databaseTool);
    }

    protected static function getKernelClass(): string
    {
        return AppConfigPgsqlKernel::class;
    }
}
