<?php

declare(strict_types=1);

namespace Liip\Acme\Tests\Test;

use Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName\AppConfigSqliteDifferentConnectionNameKernel;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;

/**
 * Test SQLite database with entity manager having different name than his connection.
 *
 * @internal
 */
class ConfigSqliteDifferentConnectionNameTest extends ConfigSqliteTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $testContainer = static::getContainer();

        $this->databaseTool = $testContainer->get(DatabaseToolCollection::class)->get('different');
    }

    public static function getKernelClass(): string
    {
        return AppConfigSqliteDifferentConnectionNameKernel::class;
    }
}
