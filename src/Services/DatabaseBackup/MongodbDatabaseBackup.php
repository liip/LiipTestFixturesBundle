<?php

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\TestFixturesBundle\Services\DatabaseBackup;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Driver\Server;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
final class MongodbDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    protected static $referenceData;

    protected static $metadata;

    protected static $databases;

    public function getBackupFilePath(): string
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_mongodb_'.md5(serialize($this->metadatas).serialize($this->classNames));
    }

    public function getReferenceBackupFilePath(): string
    {
        return $this->getBackupFilePath().'.ser';
    }

    protected function getReferenceBackup(): string
    {
        if (empty(self::$referenceData)) {
            self::$referenceData = file_get_contents($this->getReferenceBackupFilePath());
        }

        return self::$referenceData;
    }

    public function isBackupActual(): bool
    {
        return
            file_exists($this->getBackupFilePath()) &&
            file_exists($this->getReferenceBackupFilePath()) &&
            $this->isBackupUpToDate($this->getBackupFilePath());
    }

    protected function getDatabases(DocumentManager $dm): array
    {
        if (!self::$databases) {
            self::$databases = [];
            foreach ($dm->getDocumentDatabases() as $db) {
                $hosts = $db->getManager()->getServers();

                foreach ($hosts as $host) {
                    self::$databases[$db->getDatabaseName()] = $host;
                }
            }
        }

        return self::$databases;
    }

    public function backup(AbstractExecutor $executor): void
    {
        /** @var DocumentManager $dm */
        $dm = $executor->getReferenceRepository()->getManager();

        foreach ($this->getDatabases($dm) as $dbName => $server) {
            /**
             * @var $server Server
             */
            $dbHost = $server->getHost();
            $dbPort = $server->getPort();

            exec("mongodump --quiet --forceTableScan --db $dbName --host $dbHost --port $dbPort --out {$this->getBackupFilePath()}");
        }

        $executor->getReferenceRepository()->save($this->getBackupFilePath());
        self::$metadata = $dm->getMetadataFactory()->getLoadedMetadata();
    }

    public function restore(AbstractExecutor $executor, array $excludedTables = []): void
    {
        /** @var DocumentManager $dm */
        $dm = $executor->getReferenceRepository()->getManager();

        foreach ($this->getDatabases($dm) as $dbName => $server) {
            /**
             * @var $server Server
             */
            $dbHost = $server->getHost();
            $dbPort = $server->getPort();

            exec("mongorestore --quiet --drop --db $dbName --host $dbHost --port $dbPort {$this->getBackupFilePath()}/$dbName", $output);
        }

        if (self::$metadata) {
            // it need for better performance
            foreach (self::$metadata as $class => $data) {
                $dm->getMetadataFactory()->setMetadataFor($class, $data);
            }
            $executor->getReferenceRepository()->unserialize($this->getReferenceBackup());
        } else {
            $executor->getReferenceRepository()->unserialize($this->getReferenceBackup());
            self::$metadata = $dm->getMetadataFactory()->getLoadedMetadata();
        }
    }
}
