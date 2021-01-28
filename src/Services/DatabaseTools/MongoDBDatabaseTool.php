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
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class MongoDBDatabaseTool extends AbstractDatabaseTool
{
    protected static $databaseCreated = false;

    public function getType(): string
    {
        return 'MongoDB';
    }

    protected function getExecutor(MongoDBPurger $purger = null): MongoDBExecutor
    {
        return new MongoDBExecutor($this->om, $purger);
    }

    protected function getPurger(): MongoDBPurger
    {
        return new MongoDBPurger();
    }

    protected function createDatabaseOnce(): void
    {
        if (!self::$databaseCreated) {
            $sm = $this->om->getSchemaManager();
            $sm->updateIndexes();
            self::$databaseCreated = true;
        }
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $this->createDatabaseOnce();

        $backupService = $this->getBackupService();
        if ($backupService) {
            $backupService->init($this->getMetadatas(), $classNames);

            if ($backupService->isBackupActual()) {

                $this->om->flush();
                $this->om->clear();

                if ($this->testCase && method_exists($this->testCase, 'preFixtureBackupRestore')) {
                    $this->testCase->preFixtureBackupRestore($this->om, $referenceRepository, $backupService->getBackupFilePath());
                }
                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor);
                if ($this->testCase && method_exists($this->testCase, 'postFixtureBackupRestore')) {
                    $this->testCase->postFixtureBackupRestore($backupService->getBackupFilePath());
                }

                return $executor;
            }
        }

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
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
}
