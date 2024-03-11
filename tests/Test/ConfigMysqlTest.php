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

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectRepository;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigMysql\AppConfigMysqlKernel;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test MySQL database.
 *
 * The following tests require a connection to a MySQL database.
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

    public function testLoadEmptyFixtures(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    public function testLoadFixtures(int $firstUserId = 1): void
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

    public function testAppendFixtures(int $firstUserId = 1, int $thirdUserId = 3): void
    {
        $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->databaseTool->loadFixtures(
            ['Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData'],
            true
        );

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

        /** @var User $user */
        $user3 = $this->userRepository
            ->findOneBy([
                'email' => 'bar@foo.com',
            ])
        ;

        $this->assertNotNull($user3);

        $this->assertSame(
            'bar@foo.com',
            $user3->getEmail()
        );
    }

    /**
     * Data fixtures and purge.
     *
     * Purge modes are defined in
     * Doctrine\Common\DataFixtures\Purger\ORMPurger.
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

        $this->databaseTool->setExcludedDoctrineTables([]);
    }

    /**
     * Data fixtures and purge.
     *
     * Purge modes are defined in
     * Doctrine\Common\DataFixtures\Purger\ORMPurger.
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
