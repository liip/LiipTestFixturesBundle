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

use Liip\Acme\Tests\AppConfigMysqlUrl\AppConfigMysqlUrlKernel;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Test MySQL database with a configuration by URL.
 *
 * The following tests require a connection to a MySQL database.
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigMysql/config.yml file.
 *
 * Use Tests/AppConfigMysql/AppConfigMysqlUrlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigMysqlUrlTest extends ConfigMysqlTest
{
    protected static function getKernelClass(): string
    {
        return AppConfigMysqlUrlKernel::class;
    }
}
