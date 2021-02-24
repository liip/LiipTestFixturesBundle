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

use BadMethodCallException;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use Liip\TestFixturesBundle\Services\DatabaseBackup\DatabaseBackupInterface;
use Liip\TestFixturesBundle\Services\FixturesLoaderFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
abstract class AbstractDatabaseTool
{
    const KEEP_DATABASE_AND_SCHEMA_PARAMETER_NAME = 'liip_test_fixtures.keep_database_and_schema';
    const CACHE_METADATA_PARAMETER_NAME = 'liip_test_fixtures.cache_metadata';

    protected $container;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    protected $fixturesLoaderFactory;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var null|string
     */
    protected $omName;

    /**
     * @var string
     */
    protected $registryName = 'doctrine';

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var null|int
     */
    protected $purgeMode;

    /**
     * @var bool
     */
    protected $databaseCacheEnabled = true;

    protected $excludedDoctrineTables = [];

    /**
     * @var array
     */
    private static $cachedMetadatas = [];

    public function __construct(ContainerInterface $container, FixturesLoaderFactory $fixturesLoaderFactory)
    {
        $this->container = $container;
        $this->eventDispatcher = $container->get('event_dispatcher');
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

    public function isDatabaseCacheEnabled(): bool
    {
        return $this->databaseCacheEnabled;
    }

    public function setObjectManagerName(string $omName = null): void
    {
        $this->omName = $omName;
        $this->om = $this->registry->getManager($omName);
        $this->connection = $this->registry->getConnection($omName);
    }

    public function setRegistryName(string $registryName): void
    {
        $this->registryName = $registryName;
    }

    public function setPurgeMode(int $purgeMode = null): void
    {
        $this->purgeMode = $purgeMode;
    }

    abstract public function getType(): string;

    public function getDriverName(): string
    {
        return 'default';
    }

    public function withObjectManagerName(string $omName): AbstractDatabaseTool
    {
        $newTool = clone $this;
        $newTool->setObjectManagerName($omName);

        return $newTool;
    }

    public function withRegistryName(string $registryName): AbstractDatabaseTool
    {
        $newTool = clone $this;
        $newTool->setRegistryName($registryName);

        /** @var \Symfony\Bridge\Doctrine\ManagerRegistry $registry */
        $registry = $this->container->get($registryName);

        $newTool->setRegistry($registry);

        return $newTool;
    }

    public function withPurgeMode(int $purgeMode): AbstractDatabaseTool
    {
        $newTool = clone $this;
        $newTool->setPurgeMode($purgeMode);

        return $newTool;
    }

    public function withDatabaseCacheEnabled(bool $databaseCacheEnabled): AbstractDatabaseTool
    {
        $newTool = clone $this;
        $newTool->setDatabaseCacheEnabled($databaseCacheEnabled);

        return $newTool;
    }

    abstract public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor;

    /**
     * @throws BadMethodCallException
     */
    public function loadAliceFixture(array $paths = [], bool $append = false): array
    {
        $persisterLoaderServiceName = 'fidry_alice_data_fixtures.loader.doctrine';
        if (!$this->container->has($persisterLoaderServiceName)) {
            throw new BadMethodCallException('theofidry/alice-data-fixtures must be installed to use this method.');
        }

        if (false === $append) {
            $this->cleanDatabase();
        }

        $files = $this->locateResources($paths);

        return $this->container->get($persisterLoaderServiceName)->load($files);
    }

    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
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
            } else {
                @trigger_error("Could not find {$backupServiceName} in container. Possible misconfiguration.");
            }
        }

        return (isset($backupService) && $backupService instanceof DatabaseBackupInterface) ? $backupService : null;
    }

    protected function cleanDatabase(): void
    {
        $this->loadFixtures([]);
    }

    /**
     * Locate fixture files.
     *
     * @throws InvalidArgumentException if a wrong path is given outside a bundle
     */
    protected function locateResources(array $paths): array
    {
        $files = [];

        $kernel = $this->container->get('kernel');

        foreach ($paths as $path) {
            if ('@' !== $path[0]) {
                if (!file_exists($path)) {
                    throw new InvalidArgumentException(sprintf('Unable to find file "%s".', $path));
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
        if (!$this->getCacheMetadataParameter()) {
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
