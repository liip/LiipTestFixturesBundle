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

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

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
