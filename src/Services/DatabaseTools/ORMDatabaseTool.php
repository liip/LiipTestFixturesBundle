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
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @var EntityManager
     */
    protected $om;

    /**
     * @var bool
     */
    private $shouldEnableForeignKeyChecks = false;

    public function getType(): string
    {
        return 'ORM';
    }

    protected function getExecutor(ORMPurger $purger = null): ORMExecutor
    {
        return new ORMExecutor($this->om, $purger);
    }

    protected function getPurger(): ORMPurger
    {
        $purger = new ORMPurger(null, $this->excludedDoctrineTables);

        if (null !== $this->purgeMode) {
            $purger->setPurgeMode($this->purgeMode);
        }

        return $purger;
    }

    protected function createDatabaseIfNotExists(): void
    {
        $params = $this->connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }
        $dbName = isset($params['dbname']) ? $params['dbname'] : '';

        unset($params['dbname']);

        // Unset url to avoid issue:
        // “An exception occurred in driver: SQLSTATE[HY000] [1049] Unknown database 'test'”
        unset($params['url']);

        $tmpConnection = DriverManager::getConnection($params);
        $tmpConnection->connect();

        if (!in_array($dbName, $tmpConnection->getSchemaManager()->listDatabases())) {
            $tmpConnection->getSchemaManager()->createDatabase($dbName);
        }

        $tmpConnection->close();
    }

    protected function cleanDatabase(): void
    {
        $this->disableForeignKeyChecksIfApplicable();

        $this->loadFixtures([]);

        $this->enableForeignKeyChecksIfApplicable();
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if (false === $this->getKeepDatabaseAndSchemaParameter()) {
            $this->createDatabaseIfNotExists();
        }

        $backupService = $this->getBackupService();

        if ($backupService && $this->databaseCacheEnabled) {
            $backupService->init($this->getMetadatas(), $classNames, $append);

            if ($backupService->isBackupActual()) {
                if (null !== $this->connection) {
                    $this->connection->close();
                }

                $this->om->flush();
                $this->om->clear();

                $this->testCase->preFixtureBackupRestore($this->om, $referenceRepository, $backupService->getBackupFilePath());
                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor, $this->excludedDoctrineTables);
                $this->testCase->postFixtureBackupRestore($backupService->getBackupFilePath());

                return $executor;
            }
        }

        // TODO: handle case when using persistent connections. Fail loudly?
        if (false === $this->getKeepDatabaseAndSchemaParameter()) {

            $schemaTool = new SchemaTool($this->om);
            if (count($this->excludedDoctrineTables) > 0 || true === $append) {
                if (!empty($this->getMetadatas())) {
                    $schemaTool->updateSchema($this->getMetadatas());
                }
            } else {
                $schemaTool->dropDatabase();
                if (!empty($this->getMetadatas())) {
                    $schemaTool->createSchema($this->getMetadatas());
                }
            }
        }

        $this->testCase->postFixtureSetup();

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $this->disableForeignKeyChecksIfApplicable();
            $executor->purge();
            $this->enableForeignKeyChecksIfApplicable();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        if ($backupService) {
            $this->testCase->preReferenceSave($this->om, $executor, $backupService->getBackupFilePath());
            $backupService->backup($executor);
            $this->testCase->postReferenceSave($this->om, $executor, $backupService->getBackupFilePath());
        }

        return $executor;
    }

    private function disableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isMysql()) {
            return;
        }

        $currentValue = $this->connection->fetchColumn('SELECT @@SESSION.foreign_key_checks');
        if ($currentValue === '0') {
            return;
        }

        $this->connection->query('SET FOREIGN_KEY_CHECKS=0');
        $this->shouldEnableForeignKeyChecks = true;
    }

    private function enableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isMysql()) {
            return;
        }

        if (!$this->shouldEnableForeignKeyChecks) {
            return;
        }

        $this->connection->query('SET FOREIGN_KEY_CHECKS=1');
        $this->shouldEnableForeignKeyChecks = false;
    }

    private function isMysql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof MySqlPlatform;
    }
}
