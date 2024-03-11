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
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\TestFixturesBundle\Event\FixtureEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @var EntityManager
     */
    protected $om;

    private bool $shouldEnableForeignKeyChecks = false;

    protected Connection $connection;

    public function setObjectManagerName(?string $omName = null): void
    {
        parent::setObjectManagerName($omName);
        $this->connection = $this->registry->getConnection($omName);
    }

    public function getType(): string
    {
        return 'ORM';
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

        if (false === $this->getKeepDatabaseAndSchemaParameter()) {
            $this->createDatabaseIfNotExists();

            // TODO: handle case when using persistent connections. Fail loudly?
            $schemaTool = new SchemaTool($this->om);
            if (\count($this->excludedDoctrineTables) > 0 || true === $append) {
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

        $event = new FixtureEvent();
        $this->eventDispatcher->dispatch($event, LiipTestFixturesEvents::POST_FIXTURE_SETUP);

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $this->disableForeignKeyChecksIfApplicable();
            $executor->purge();
            $this->enableForeignKeyChecksIfApplicable();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        return $executor;
    }

    protected function getExecutor(?ORMPurger $purger = null): ORMExecutor
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

        // doctrine-bundle >= 2.2
        if (isset($params['primary'])) {
            $params = $params['primary'];
        }
        // doctrine-bundle < 2.2
        elseif (isset($params['master'])) {
            $params = $params['master'];
        }
        $dbName = $params['dbname'] ?? '';

        unset($params['dbname'], $params['url']);

        // Unset url to avoid issue:
        // “An exception occurred in driver: SQLSTATE[HY000] [1049] Unknown database 'test'”

        $tmpConnection = DriverManager::getConnection($params);

        $schemaManager = $tmpConnection->createSchemaManager();

        // DBAL 4.x does not support creating databases for SQLite anymore; for now we silently ignore this error
        try {
            if (!\in_array($dbName, $schemaManager->listDatabases(), true)) {
                $schemaManager->createDatabase($dbName);
            }
        } catch (\Doctrine\DBAL\Platforms\Exception\NotSupported $e) {
        }

        $tmpConnection->close();
    }

    protected function cleanDatabase(): void
    {
        $this->disableForeignKeyChecksIfApplicable();

        $this->loadFixtures([]);

        $this->enableForeignKeyChecksIfApplicable();
    }

    protected function disableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isMysql()) {
            return;
        }

        // Doctrine DBAL 2.x deprecated fetchColumn() in favor of fetchOne()
        if (method_exists($this->connection, 'fetchColumn')) {
            $currentValue = $this->connection->fetchColumn('SELECT @@SESSION.foreign_key_checks');
        } else {
            $currentValue = $this->connection->fetchOne('SELECT @@SESSION.foreign_key_checks');
        }

        if ('0' === $currentValue) {
            return;
        }

        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $this->shouldEnableForeignKeyChecks = true;
    }

    protected function enableForeignKeyChecksIfApplicable(): void
    {
        if (!$this->isMysql()) {
            return;
        }

        if (!$this->shouldEnableForeignKeyChecks) {
            return;
        }

        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $this->shouldEnableForeignKeyChecks = false;
    }

    private function isMysql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof MySqlPlatform;
    }
}
