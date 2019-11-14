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
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Liip\TestFixturesBundle\Services\DatabaseBackup\DatabaseBackupInterface;
use Liip\TestFixturesBundle\Services\FixturesLoaderFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
abstract class AbstractDatabaseTool
{
    const KEEP_DATABASE_AND_SCHEMA_PARAMETER_NAME = 'liip_test_fixtures.keep_database_and_schema';
    const CACHE_METADATA_PARAMETER_NAME = 'liip_test_fixtures.cache_metadata';

    protected $container;

    protected $fixturesLoaderFactory;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $omName;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var int
     */
    protected $purgeMode;

    /**
     * @var KernelTestCase
     */
    protected $testCase;

    /**
     * @var bool
     */
    protected $databaseCacheEnabled = true;

    /**
     * @var array
     */
    private static $cachedMetadatas = [];

    protected $excludedDoctrineTables = [];

    public function __construct(ContainerInterface $container, FixturesLoaderFactory $fixturesLoaderFactory)
    {
        $this->container = $container;
        $this->fixturesLoaderFactory = $fixturesLoaderFactory;
    }

    public function setRegistry(ManagerRegistry $registry): void
    {
        $this->registry = $registry;
    }

    public function setDatabaseCacheEnabled(bool $databaseCacheEnabled): void
    {
        $this->databaseCacheEnabled = $databaseCacheEnabled;
    }

    public function setObjectManagerName(string $omName = null): void
    {
        $this->omName = $omName;
        $this->om = $this->registry->getManager($omName);
        $this->connection = $this->registry->getConnection($omName);
    }

    public function setPurgeMode(int $purgeMode = null): void
    {
        $this->purgeMode = $purgeMode;
    }

    public function setTestCase(KernelTestCase $testCase): void
    {
        $this->testCase = $testCase;
    }

    abstract public function getType(): string;

    public function getDriverName(): string
    {
        return 'default';
    }

    protected function getBackupService(): ?DatabaseBackupInterface
    {
        $backupServiceParamName = strtolower('liip_test_fixtures.cache_db.'.(
            ('ORM' === $this->getType())
                ? $this->connection->getDatabasePlatform()->getName()
                : $this->getType()
        ));

        if ($this->container->hasParameter($backupServiceParamName)) {
            $backupServiceName = $this->container->getParameter($backupServiceParamName);
            if ($this->container->has($backupServiceName)) {
                $backupService = $this->container->get($backupServiceName);
            }
        }

        return (isset($backupService) && $backupService instanceof DatabaseBackupInterface) ? $backupService : null;
    }

    abstract public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor;

    /**
     * @throws \BadMethodCallException
     */
    public function loadAliceFixture(array $paths = [], bool $append = false): array
    {
        $persisterLoaderServiceName = 'fidry_alice_data_fixtures.loader.doctrine';
        if (!$this->container->has($persisterLoaderServiceName)) {
            throw new \BadMethodCallException('theofidry/alice-data-fixtures must be installed to use this method.');
        }

        if (false === $append) {
            $this->cleanDatabase();
        }

        $files = $this->locateResources($paths);

        return $this->container->get($persisterLoaderServiceName)->load($files);
    }

    protected function cleanDatabase(): void
    {
        $this->loadFixtures([]);
    }

    /**
     * Locate fixture files.
     *
     * @throws \InvalidArgumentException if a wrong path is given outside a bundle
     */
    protected function locateResources(array $paths): array
    {
        $files = [];

        $kernel = $this->container->get('kernel');

        foreach ($paths as $path) {
            if ('@' !== $path[0]) {
                if (!file_exists($path)) {
                    throw new \InvalidArgumentException(sprintf('Unable to find file "%s".', $path));
                }
                $files[] = $path;

                continue;
            }

            $files[] = $kernel->locateResource($path);
        }

        return $files;
    }

    protected function getMetadatas(): array
    {
        if(!$this->getCacheMetadataParameter()) {
            return $this->om->getMetadataFactory()->getAllMetadata();
        }

        $key = $this->getDriverName().$this->getType().$this->omName;

        if (!isset(self::$cachedMetadatas[$key])) {
            self::$cachedMetadatas[$key] = $this->om->getMetadataFactory()->getAllMetadata();
            usort(self::$cachedMetadatas[$key], function ($a, $b) {
                return strcmp($a->name, $b->name);
            });
        }

        return self::$cachedMetadatas[$key];
    }

    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
    }

    protected function getKeepDatabaseAndSchemaParameter()
    {
        return $this->container->hasParameter(self::KEEP_DATABASE_AND_SCHEMA_PARAMETER_NAME)
            && true === $this->container->getParameter(self::KEEP_DATABASE_AND_SCHEMA_PARAMETER_NAME);
    }

    protected function getCacheMetadataParameter()
    {
        return $this->container->hasParameter(self::CACHE_METADATA_PARAMETER_NAME)
            && false !== $this->container->getParameter(self::CACHE_METADATA_PARAMETER_NAME);
    }
}
