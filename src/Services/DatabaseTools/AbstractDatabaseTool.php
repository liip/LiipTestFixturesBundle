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

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Liip\TestFixturesBundle\FixturesLoaderFactoryInterface;
use Liip\TestFixturesBundle\Services\DatabaseBackup\DatabaseBackupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
abstract class AbstractDatabaseTool
{
    public const KEEP_DATABASE_AND_SCHEMA_PARAMETER_NAME = 'liip_test_fixtures.keep_database_and_schema';
    public const CACHE_METADATA_PARAMETER_NAME = 'liip_test_fixtures.cache_metadata';

    protected $container;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    protected FixturesLoaderFactoryInterface $fixturesLoaderFactory;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string|null
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
     * @var int|null
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

    public function __construct(ContainerInterface $container, FixturesLoaderFactoryInterface $fixturesLoaderFactory)
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

    public function setObjectManagerName(?string $omName = null): void
    {
        $this->omName = $omName;
        $this->om = $this->registry->getManager($omName);
    }

    public function setRegistryName(string $registryName): void
    {
        $this->registryName = $registryName;
    }

    public function setPurgeMode(?int $purgeMode = null): void
    {
        $this->purgeMode = $purgeMode;
    }

    abstract public function getType(): string;

    public function getDriverName(): string
    {
        return 'default';
    }

    public function withPurgeMode(int $purgeMode): self
    {
        $newTool = clone $this;
        $newTool->setPurgeMode($purgeMode);

        return $newTool;
    }

    public function withDatabaseCacheEnabled(bool $databaseCacheEnabled): self
    {
        $newTool = clone $this;
        $newTool->setDatabaseCacheEnabled($databaseCacheEnabled);

        return $newTool;
    }

    abstract public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor;

    /**
     * This loads all the fixtures defined in the project, including ordering
     * them, e.g. by the DependentFixtureInterface. The call to this method
     * does the same as running the console command doctrine:fixtures:load,
     * including the use of the group parameter.
     */
    public function loadAllFixtures(array $groups = []): ?AbstractExecutor
    {
        /** @var SymfonyFixturesLoader $loader */
        $fixtureClasses = [];
        if ($this->container->has('test.service_container')) {
            $loader = $this->container->get('test.service_container')->get('doctrine.fixtures.loader');
            $fixtures = $loader->getFixtures($groups);
            foreach ($fixtures as $fixture) {
                $fixtureClasses[] = $fixture::class;
            }
        }

        return $this->loadFixtures($fixtureClasses);
    }

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

            // Clear the entity manager to avoid the exception `EntityIdentityCollisionException`
            $this->om->clear();
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
                ? $this->getPlatformName()
                : $this->getType()
        ));

        if ($this->container->hasParameter($backupServiceParamName)) {
            $backupServiceName = $this->container->getParameter($backupServiceParamName);
            if (\is_string($backupServiceName) && $this->container->has($backupServiceName)) {
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

    abstract protected function getPlatformName(): string;
}
