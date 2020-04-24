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

namespace Liip\TestFixturesBundle\Test;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

/**
 * @author Lea Haensenberger
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
trait FixturesTrait
{
    protected $containers;

    /**
     * @var array
     */
    private $excludedDoctrineTables = [];

    /**
     * Get an instance of the dependency injection container.
     * (this creates a kernel *without* parameters).
     */
    protected function getContainer(): ContainerInterface
    {
        $environment = $this->determineEnvironment();

        if (empty($this->containers[$environment])) {
            $options = [
                'environment' => $environment,
            ];

            // Check that the kernel has not been booted separately (eg. with static::createClient())
            if (null === static::$kernel || null === static::$kernel->getContainer()) {
                $this->bootKernel($options);
            }

            $container = static::$kernel->getContainer();
            if ($container->has('test.service_container')) {
                $this->containers[$environment] = $container->get('test.service_container');
            } else {
                $this->containers[$environment] = $container;
            }
        }

        return $this->containers[$environment];
    }

    /**
     * Set the database to the provided fixtures.
     *
     * Drops the current database and then loads fixtures using the specified
     * classes. The parameter is a list of fully qualified class names of
     * classes that implement Doctrine\Common\DataFixtures\FixtureInterface
     * so that they can be loaded by the DataFixtures Loader::addFixture
     *
     * When using SQLite this method will automatically make a copy of the
     * loaded schema and fixtures which will be restored automatically in
     * case the same fixture classes are to be loaded again. Caveat: changes
     * to references and/or identities may go undetected.
     *
     * Depends on the doctrine data-fixtures library being available in the
     * class path.
     */
    protected function loadFixtures(array $classNames = [], bool $append = false, ?string $omName = null, string $registryName = 'doctrine', ?int $purgeMode = null): ?AbstractExecutor
    {
        $container = $this->getContainer();

        $dbToolCollection = $container->get('liip_test_fixtures.services.database_tool_collection');
        $dbTool = $dbToolCollection->get($omName, $registryName, $purgeMode, $this);
        $dbTool->setExcludedDoctrineTables($this->excludedDoctrineTables);

        return $dbTool->loadFixtures($classNames, $append);
    }

    public function loadFixtureFiles(array $paths = [], bool $append = false, ?string $omName = null, $registryName = 'doctrine', ?int $purgeMode = null): array
    {
        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        $dbToolCollection = $container->get('liip_test_fixtures.services.database_tool_collection');
        $dbTool = $dbToolCollection->get($omName, $registryName, $purgeMode, $this);
        $dbTool->setExcludedDoctrineTables($this->excludedDoctrineTables);

        return $dbTool->loadAliceFixture($paths, $append);
    }

    /**
     * Callback function to be executed after Schema creation.
     * Use this to execute acl:init or other things necessary.
     */
    public function postFixtureSetup(): void
    {
    }

    /**
     * Callback function to be executed after Schema restore.
     *
     * @param string $backupFilePath Path of file used to backup the references of the data fixtures
     */
    public function postFixtureBackupRestore($backupFilePath): void
    {
    }

    /**
     * Callback function to be executed before Schema restore.
     */
    public function preFixtureBackupRestore(
        ObjectManager $manager,
        ProxyReferenceRepository $referenceRepository,
        string $backupFilePath
    ): void {
    }

    /**
     * Callback function to be executed after save of references.
     */
    public function postReferenceSave(ObjectManager $manager, AbstractExecutor $executor, string $backupFilePath): void
    {
    }

    /**
     * Callback function to be executed before save of references.
     */
    public function preReferenceSave(ObjectManager $manager, AbstractExecutor $executor, ?string $backupFilePath): void
    {
    }

    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
    }

    protected function tearDown(): void
    {
        if (null !== $this->containers) {
            foreach ($this->containers as $container) {
                if ($container instanceof ResettableContainerInterface) {
                    $container->reset();
                }
            }
        }

        $this->containers = null;

        parent::tearDown();
    }

    /**
     * @see KernelTestCase::createKernel()
     */
    private function determineEnvironment()
    {
        if (isset($_ENV['APP_ENV'])) {
            return $_ENV['APP_ENV'];
        }

        if (isset($_SERVER['APP_ENV'])) {
            return $_SERVER['APP_ENV'];
        }

        return 'test';
    }
}
