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

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectRepository;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigMysql\AppConfigMysqlKernel;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;

// BC, needed by "theofidry/alice-data-fixtures: <1.3" not compatible with "doctrine/persistence: ^2.0"
if (interface_exists('\Doctrine\Persistence\ObjectManager')
    && !interface_exists('\Doctrine\Common\Persistence\ObjectManager')) {
    class_alias('\Doctrine\Persistence\ObjectManager', '\Doctrine\Common\Persistence\ObjectManager');
}

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
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 *
 * @IgnoreAnnotation("group")
 *
 * @internal
 */
class ConfigMysqlTest extends FixturesTestCase
{
    use ContainerProvider;

    /** @var ObjectRepository */
    protected $userRepository;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->userRepository = $this->getTestContainer()->get('doctrine')
            ->getRepository(User::class)
        ;

        $this->databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get();

        $this->assertInstanceOf(ORMDatabaseTool::class, $this->databaseTool);
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
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
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
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );

        $user1 = $repository->getReference('user');

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
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $referenceRepository = $this->databaseTool->loadFixtures(
            ['Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData'],
            true
        )->getReferenceRepository();

        $this->assertCount(2, $referenceRepository->getReferences());

        // Load data from database
        $users = $this->userRepository->findAll();

        // Check that there are 3 users.
        $this->assertCount(
            3,
            $users
        );

        /** @var User $user */
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

        /** @var User $user2 */
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

        /** @var User $user3 */
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
     * Doctrine\Common\DataFixtures\Purger\ORMPurger.
     *
     * @group mysql
     */
    public function testLoadFixturesAndExcludeFromPurge(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
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
     * Doctrine\Common\DataFixtures\Purger\ORMPurger.
     *
     * @group mysql
     * @group pgsql
     */
    public function testLoadFixturesAndPurge(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
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

        $this->getTestContainer()->get('doctrine')->getManager()->clear();

        // Reload fixtures
        $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
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

        $this->assertIsArray($fixtures);

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
     */
    public function testLoadDependentFixtures(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadDependentUserData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $users = $this->userRepository->findAll();

        // The two files with fixtures have been loaded, there are 4 users.
        $this->assertCount(
            4,
            $users
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
