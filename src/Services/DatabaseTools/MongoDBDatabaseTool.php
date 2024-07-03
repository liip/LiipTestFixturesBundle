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
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\ODM\MongoDB\Configuration;
use Liip\TestFixturesBundle\Event\PostFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\PreFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\ReferenceSaveEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;

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

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);

        /** @var Configuration $config */
        $config = $this->om->getConfiguration();

        $cacheDriver = $config->getMetadataCache();

        if ($cacheDriver) {
            $cacheDriver->clear();
        }

        $this->createDatabaseOnce();

        $backupService = $this->getBackupService();
        if ($backupService) {
            $backupService->init($this->getMetadatas(), $classNames);

            if ($backupService->isBackupActual()) {
                $this->om->flush();
                $this->om->clear();

                $event = new PreFixtureBackupRestoreEvent($this->om, $referenceRepository, $backupService->getBackupFilePath());
                $this->eventDispatcher->dispatch($event, LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE);

                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor);

                $event = new PostFixtureBackupRestoreEvent($backupService->getBackupFilePath());
                $this->eventDispatcher->dispatch($event, LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE);

                return $executor;
            }
        }

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();

            // Clear the entity manager to avoid the exception `EntityIdentityCollisionException`
            $this->om->clear();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        if ($backupService) {
            $event = new ReferenceSaveEvent($this->om, $executor, $backupService->getBackupFilePath());
            $this->eventDispatcher->dispatch($event, LiipTestFixturesEvents::PRE_REFERENCE_SAVE);

            $backupService->backup($executor);

            $this->eventDispatcher->dispatch($event, LiipTestFixturesEvents::POST_REFERENCE_SAVE);
        }

        return $executor;
    }

    protected function getExecutor(?MongoDBPurger $purger = null): MongoDBExecutor
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

    protected function getPlatformName(): string
    {
        return 'mongodb';
    }
}
