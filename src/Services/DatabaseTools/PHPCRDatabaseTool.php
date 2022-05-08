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

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerManager;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\PHPCRPurger;
use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Liip\TestFixturesBundle\Event\PostFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\PreFixtureBackupRestoreEvent;
use Liip\TestFixturesBundle\Event\ReferenceSaveEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class PHPCRDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @var DocumentManager
     */
    protected $om;

    public function getType(): string
    {
        return 'PHPCR';
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);

        /** @var Configuration $config */
        $config = $this->om->getConfiguration();

        $cacheDriver = $config->getMetadataCacheImpl();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $backupService = $this->getBackupService();
        if ($backupService) {
            $backupService->init($this->getMetadatas(), $classNames);

            if ($backupService->isBackupActual()) {
                if (null !== $this->connection) {
                    $this->connection->close();
                }

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

        $executor = $this->getExecutor($this->getPurger(), $this->getInitializerManager());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
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

    protected function getExecutor(PHPCRPurger $purger = null, InitializerManager $initializerManager = null): AbstractExecutor
    {
        $executorClass = class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
            ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$this->getType().'Executor';

        return new $executorClass($this->om, $purger, $initializerManager);
    }

    protected function getPurger(): PHPCRPurger
    {
        return new PHPCRPurger($this->om);
    }

    protected function getInitializerManager(): ?InitializerManager
    {
        $serviceName = 'doctrine_phpcr.initializer_manager';

        return $this->container->has($serviceName) ? $this->container->get($serviceName) : null;
    }
}
