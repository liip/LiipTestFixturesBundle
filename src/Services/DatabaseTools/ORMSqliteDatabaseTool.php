<?php

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
use Doctrine\ORM\Tools\SchemaTool;
use Liip\TestFixturesBundle\Event\FixtureEvent;
use Liip\TestFixturesBundle\Event\PostFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\PreFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\ReferenceSaveEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMSqliteDatabaseTool extends ORMDatabaseTool
{
    /**
     * @var bool
     */
    private $shouldEnableForeignKeyChecks = false;

    public function getDriverName(): string
    {
        return 'pdo_sqlite';
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $backupService = $this->getBackupService();
        if ($backupService && $this->databaseCacheEnabled) {
            $backupService->init($this->getMetadatas(), $classNames);

            if ($backupService->isBackupActual()) {
                if (null !== $this->connection) {
                    $this->connection->close();
                }

                $this->om->flush();
                $this->om->clear();

                $event = new PreFixtureBackupRestoreEvent($this->om, $referenceRepository, $backupService->getBackupFilePath());
                $this->dispatchEvent($event, LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE);
                $this->testCase->preFixtureBackupRestore($this->om, $referenceRepository, $backupService->getBackupFilePath());

                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor);

                $event = new PostFixtureBackupRestoreEvent($backupService->getBackupFilePath());
                $this->dispatchEvent($event, LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE);
                $this->testCase->postFixtureBackupRestore($backupService->getBackupFilePath());

                return $executor;
            }
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
        $this->dispatchEvent($event, LiipTestFixturesEvents::POST_FIXTURE_SETUP);

        $this->testCase->postFixtureSetup();

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        if ($backupService) {
            $event = new ReferenceSaveEvent($this->om, $executor, $backupService->getBackupFilePath());
            $this->dispatchEvent($event, LiipTestFixturesEvents::PRE_REFERENCE_SAVE);
            $this->testCase->preReferenceSave($this->om, $executor, $backupService->getBackupFilePath());

            $backupService->backup($executor);

            $this->dispatchEvent($event, LiipTestFixturesEvents::POST_REFERENCE_SAVE);
            $this->testCase->postReferenceSave($this->om, $executor, $backupService->getBackupFilePath());
        }

        return $executor;
    }

    protected function disableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isSqlite()) {
            return;
        }

        $currentValue = $this->connection->fetchColumn('PRAGMA foreign_keys');
        if ($currentValue === '0') {
            return;
        }

        $this->connection->query('PRAGMA foreign_keys = 0');
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

        $this->connection->query('PRAGMA foreign_keys = 1');
        $this->shouldEnableForeignKeyChecks = false;
    }

    private function isSqlite(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof SqlitePlatform;
    }
}
