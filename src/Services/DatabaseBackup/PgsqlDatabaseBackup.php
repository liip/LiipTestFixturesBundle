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

namespace Liip\TestFixturesBundle\Services\DatabaseBackup;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\ORM\EntityManager;

/**
 * @author Nikita Slutsky <slutsky6@gmail.com>
 */
final class PgsqlDatabaseBackup extends AbstractDatabaseBackup
{
    public function getBackupFilePath(): string
    {
        $cacheDir = $this->container->getParameter('kernel.cache_dir');
        $hash = md5(serialize($this->metadatas).serialize($this->classNames));

        return $cacheDir.'/test_postgresql_'.$hash.'.tar';
    }

    public function isBackupActual(): bool
    {
        $backupFileName = $this->getBackupFilePath();
        $backupReferenceFileName = $backupFileName.'.ser';

        return file_exists($backupFileName)
            && file_exists($backupReferenceFileName)
            && $this->isBackupUpToDate($backupFileName);
    }

    public function backup(AbstractExecutor $executor): void
    {
        /** @var ProxyReferenceRepository $referenceRepository */
        $referenceRepository = $executor->getReferenceRepository();

        /** @var EntityManager $em */
        $em = $referenceRepository->getManager();

        $connection = $em->getConnection();
        $params = $connection->getParams();

        $referenceRepository->save($this->getBackupFilePath());
        exec($this->createCommand('pg_dump --format=t', $params).' > '.$this->getBackupFilePath());
    }

    public function restore(AbstractExecutor $executor, array $excludedTables = []): void
    {
        /** @var ProxyReferenceRepository $referenceRepository */
        $referenceRepository = $executor->getReferenceRepository();

        /** @var EntityManager $em */
        $em = $referenceRepository->getManager();

        $connection = $em->getConnection();
        $params = $connection->getParams();

        $connection->executeQuery('SET session_replication_role = \'replica\';');
        exec($this->createCommand('pg_restore --format=t --clean', $params).' '.$this->getBackupFilePath());
        $connection->executeQuery('SET session_replication_role = \'origin\';');
        $referenceRepository->load($this->getBackupFilePath());
    }

    protected function getReferenceBackup(): string
    {
        return file_get_contents($this->getBackupFilePath());
    }

    private function createCommand(string $command, array $params): string
    {
        // doctrine-bundle >= 2.2
        if (isset($params['primary'])) {
            $params = $params['primary'];
        }
        // doctrine-bundle < 2.2
        elseif (isset($params['master'])) {
            $params = $params['master'];
        }

        $command .= isset($params['dbname']) && $params['dbname'] ? ' --dbname='.$params['dbname'] : '';
        $command .= isset($params['host']) && $params['host'] ? ' --host='.$params['host'] : '';
        $command .= isset($params['port']) && $params['port'] ? ' --port='.$params['port'] : '';
        $command .= isset($params['user']) && $params['user'] ? ' --username='.$params['user'] : '';

        if (isset($params['password']) && $params['password']) {
            $command = 'PGPASSWORD='.$params['password'].' '.$command.' --no-password';
        }

        return $command;
    }
}
