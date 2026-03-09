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

namespace Liip\Acme\Tests\Test;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityRepository;
use Liip\Acme\Tests\App\DataFixtures\ORM\LoadDependentUserData;
use Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData;
use Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData;
use Liip\Acme\Tests\App\Entity\Setting;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigMysql\AppConfigMysqlKernel;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test MySQL database.
 *
 * The following tests require a connection to a MySQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigMysql/config.yml file.
 *
 * Use Tests/AppConfigMysql/AppConfigMysqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigMysqlTest extends KernelTestCase
{
    /**
     * @var EntityRepository<User>
     */
    protected EntityRepository $userRepository;

    /**
     * @var EntityRepository<Setting>
     */
    protected EntityRepository $settingRepository;

    protected ORMDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $testContainer = static::getContainer();

        $this->userRepository = $testContainer->get('doctrine')
            ->getRepository(User::class)
        ;

        $this->settingRepository = $testContainer->get('doctrine')
            ->getRepository(Setting::class)
        ;

        $this->databaseTool = $testContainer->get(DatabaseToolCollection::class)->get();
    }

    /**
     * Data fixtures.
     *
     * @group mysql
     * @group pgsql
     */
    public function testLoadEmptyFixtures(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            ORMExecutor::class,
            $fixtures
        );
    }

    /**
     * @group mysql
     * @group pgsql
     */
    public function testLoadFixtures(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            LoadUserData::class,
        ]);

        $this->assertInstanceOf(
            ORMExecutor::class,
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            ProxyReferenceRepository::class,
            $repository
        );

        $user1 = $repository->getReference('user', User::class);

        $this->assertSame('foo bar', $user1->getName());
        $this->assertSame('foo@bar.com', $user1->getEmail());

        // Load data from database
        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'email' => 'foo@bar.com',
            ])
        ;

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );
    }

    /**
     * @group mysql
     * @group pgsql
     */
    public function testAppendFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            LoadUserData::class,
        ]);

        $referenceRepository = $this->databaseTool->loadFixtures(
            [LoadSecondUserData::class],
            true,
        )->getReferenceRepository();

        $references = $referenceRepository->getReferencesByClass();

        $className = 'Liip\Acme\Tests\App\Entity\User';

        $this->assertArrayHasKey($className, $references);

        $this->assertCount(2, $references[$className]);

        // Load data from database
        $users = $this->userRepository->findAll();

        // Check that there are 3 users.
        $this->assertCount(
            3,
            $users
        );

        /** @var User|null $user1 */
        $user1 = $this->userRepository
            ->findOneBy([
                'email' => 'foo@bar.com',
            ])
        ;

        $this->assertNotNull($user1);

        $this->assertSame(
            'foo@bar.com',
            $user1->getEmail()
        );

        /** @var User|null $user2 */
        $user2 = $this->userRepository
            ->findOneBy([
                'email' => 'alice@bar.com',
            ])
        ;

        $this->assertNotNull($user2);

        $this->assertSame(
            'alice@bar.com',
            $user2->getEmail()
        );

        /** @var User|null $user3 */
        $user3 = $this->userRepository
            ->findOneBy([
                'email' => 'alice@bar.com',
            ])
        ;

        $this->assertNotNull($user3);

        $this->assertSame(
            'alice@bar.com',
            $user3->getEmail()
        );
    }

    /**
     * Data fixtures and purge.
     *
     * Purge modes are defined in
     *
     * @see ORMPurger
     */
    public function testLoadFixturesAndExcludeFromPurge(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            LoadUserData::class,
        ]);

        $this->assertInstanceOf(
            ORMExecutor::class,
            $fixtures
        );

        // Check that there are 2 users.
        $this->assertCount(
            2,
            $this->userRepository->findAll()
        );

        $this->databaseTool->setExcludedDoctrineTables(['liip_user']);
        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([])
        ;

        // The exclusion from purge worked, the user table is still alive and well.
        $this->assertCount(
            2,
            $this->userRepository->findAll()
        );
    }

    /**
     * Data fixtures and purge.
     *
     * Purge modes are defined in
     *
     * @see ORMPurger
     *
     * @group mysql
     * @group pgsql
     */
    public function testLoadFixturesAndPurge(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            LoadUserData::class,
        ]);

        $this->assertInstanceOf(
            ORMExecutor::class,
            $fixtures
        );

        $users = $this->userRepository->findAll();

        // Check that there are 2 users.
        $this->assertCount(
            2,
            $users
        );

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_DELETE)
            ->loadFixtures()
        ;

        // The purge worked: there is no user.
        $users = $this->userRepository->findAll();

        $this->assertCount(
            0,
            $users
        );

        // Reload fixtures
        $this->databaseTool->loadFixtures([
            LoadUserData::class,
        ]);

        $users = $this->userRepository->findAll();

        // Check that there are 2 users.
        $this->assertCount(
            2,
            $users
        );

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures()
        ;

        // The purge worked: there is no user.
        $this->assertCount(
            0,
            $this->userRepository->findAll()
        );
    }

    /**
     * Use nelmio/alice.
     *
     * @group mysql
     * @group pgsql
     */
    public function testLoadFixturesFiles(): void
    {
        $fixtures = $this->databaseTool->loadAliceFixture([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ]);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $users = $this->userRepository->findAll();

        $this->assertCount(
            10,
            $users
        );

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 1,
            ])
        ;

        $this->assertInstanceOf(User::class, $user);

        $user = $this->userRepository
            ->findOneBy([
                'id' => 10,
            ])
        ;

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * Load fixture which has a dependency.
     *
     * @group mysql
     * @group pgsql
     */
    public function testLoadDependentFixtures(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            LoadDependentUserData::class,
        ]);

        $this->assertInstanceOf(
            ORMExecutor::class,
            $fixtures
        );

        $users = $this->userRepository->findAll();

        // The two files with fixtures have been loaded, there are 4 users.
        $this->assertCount(
            4,
            $users
        );

        // Settings have been loaded too
        $settings = $this->settingRepository->findAll();

        $this->assertCount(
            2,
            $settings
        );
    }

    protected static function getKernelClass(): string
    {
        return AppConfigMysqlKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}
