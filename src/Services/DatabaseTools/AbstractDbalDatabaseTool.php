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

namespace Liip\TestFixturesBundle\Services\DatabaseTools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

abstract class AbstractDbalDatabaseTool extends AbstractDatabaseTool
{
    protected Connection $connection;

    public function setObjectManagerName(?string $omName = null): void
    {
        parent::setObjectManagerName($omName);
        $this->connection = $this->registry->getConnection($omName);
    }

    protected function getPlatformName(): string
    {
        $platform = $this->connection->getDatabasePlatform();

        // AbstractMySQLPlatform was introduced in DBAL 3.3, keep the MySQLPlatform checks for compatibility with older versions
        if ($platform instanceof AbstractMySQLPlatform || $platform instanceof MySqlPlatform) {
            return 'mysql';
        } elseif ($platform instanceof SqlitePlatform) {
            return 'sqlite';
        } elseif ($platform instanceof PostgreSQLPlatform) {
            return 'pgsql';
        }

        return (new \ReflectionClass($platform))->getShortName();
    }
}
