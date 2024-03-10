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

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\TestFixturesBundle\Event\FixtureEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMSqliteDatabaseTool extends ORMDatabaseTool
{
    private bool $shouldEnableForeignKeyChecks = false;

    public function getDriverName(): string
    {
        return SqlitePlatform::class;
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);

        /** @var Configuration $config */
        $config = $this->om->getConfiguration();

        $cacheDriver = $config->getMetadataCache();

        if ($cacheDriver) {
            $cacheDriver->clear();
        }

        if (false === $append && false === $this->getKeepDatabaseAndSchemaParameter()) {
            // TODO: handle case when using persistent connections. Fail loudly?
            $schemaTool = new SchemaTool($this->om);
            $schemaTool->dropDatabase();
            if (!empty($this->getMetadatas())) {
                $schemaTool->createSchema($this->getMetadatas());
            }
        }

        $event = new FixtureEvent();
        $this->eventDispatcher->dispatch($event, LiipTestFixturesEvents::POST_FIXTURE_SETUP);

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        return $executor;
    }

    protected function disableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isSqlite()) {
            return;
        }

        // Doctrine DBAL 2.x deprecated fetchColumn() in favor of fetchOne()
        if (method_exists($this->connection, 'fetchColumn')) {
            $currentValue = $this->connection->fetchColumn('PRAGMA foreign_keys');
        } else {
            $currentValue = $this->connection->fetchOne('PRAGMA foreign_keys');
        }

        if ('0' === $currentValue) {
            return;
        }

        $this->connection->executeQuery('PRAGMA foreign_keys = 0');

        $this->shouldEnableForeignKeyChecks = true;
    }

    protected function enableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isSqlite()) {
            return;
        }

        if (!$this->shouldEnableForeignKeyChecks) {
            return;
        }

        $this->connection->executeQuery('PRAGMA foreign_keys = 1');

        $this->shouldEnableForeignKeyChecks = false;
    }

    private function isSqlite(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof SqlitePlatform;
    }
}
