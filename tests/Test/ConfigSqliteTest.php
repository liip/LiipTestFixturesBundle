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

// BC, needed by "theofidry/alice-data-fixtures: <1.3" not compatible with "doctrine/persistence: ^2.0"
if (interface_exists('\Doctrine\Persistence\ObjectManager')
    && !interface_exists('\Doctrine\Common\Persistence\ObjectManager')) {
    class_alias('\Doctrine\Persistence\ObjectManager', '\Doctrine\Common\Persistence\ObjectManager');
}

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectRepository;
use InvalidArgumentException;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigSqlite\AppConfigSqliteKernel;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMSqliteDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @preserveGlobalState disabled
 *
 * @IgnoreAnnotation("depends")
 * @IgnoreAnnotation("expectedException")
 *
 * @internal
 * @coversNothing
 */
class ConfigSqliteTest extends KernelTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;
    /** @var ObjectRepository */
    private $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->userRepository = self::$container->get('doctrine')
            ->getRepository('LiipAcme:User')
        ;

        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
    }

    public static function getKernelClass()
    {
        return AppConfigSqliteKernel::class;
    }

    public function testToolType(): void
    {
        $this->assertInstanceOf(ORMSqliteDatabaseTool::class, $this->databaseTool);
    }

    public function testLoadEmptyFixtures(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    public function testLoadFixturesWithoutParameters(): void
    {
        $fixtures = $this->databaseTool->loadFixtures();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

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

        /** @var User $user1 */
        $user1 = $repository->getReference('user');

        $this->assertSame(1, $user1->getId());
        $this->assertSame('foo bar', $user1->getName());
        $this->assertSame('foo@bar.com', $user1->getEmail());
        $this->assertTrue($user1->getEnabled());

        // Load data from database
        $users = $this->userRepository->findAll();

        // There are 2 users.
        $this->assertSame(
            2,
            count($users)
        );

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 1,
            ])
        ;

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    public function testLoadAll(): void
    {
        // Load the fixtures with an invalid group. The database should be empty.
        $fixtures = $this->databaseTool->loadAllFixtures(['wrongGroup']);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );

        $users = $this->userRepository->findAll();

        // Using a non-existing group will result in zero users
        $this->assertSame(
            0,
            count($users)
        );

        // Load the fixtures with a valid group.
        $fixtures = $this->databaseTool->loadAllFixtures(['myGroup']);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );

        $users = $this->userRepository->findAll();

        // The fixture group myGroup contains 3 users
        $this->assertSame(
            3,
            count($users)
        );

        // Load all fixtures.
        $fixtures = $this->databaseTool->loadAllFixtures();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );

        $users = $this->userRepository->findAll();

        // Loading all fixtures results in 12 users.
        $this->assertSame(
            12,
            count($users)
        );
    }

    public function testAppendFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->databaseTool->loadFixtures(
            ['Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData'],
            true
        );

        // Load data from database
        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 1,
            ])
        ;

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 3,
            ])
        ;

        $this->assertSame(
            'bar@foo.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
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
        $this->assertSame(
            4,
            count($users)
        );
    }

    /**
     * Load fixture which has a dependency, with the dependent service requiring a service.
     */
    public function testLoadDependentFixturesWithDependencyInjected(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadDependentUserWithServiceData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $users = $this->userRepository->findAll();

        // The two files with fixtures have been loaded, there are 4 users.
        $this->assertSame(
            4,
            count($users)
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

        $this->assertSame(
            10,
            count($users)
        );

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 1,
            ])
        ;

        $this->assertInstanceOf(User::class, $user);

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $this->userRepository
            ->findOneBy([
                'id' => 10,
            ])
        ;

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Load nonexistent resource.
     */
    public function testLoadNonexistentFixturesFiles(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->databaseTool->loadAliceFixture([
            '@AcmeBundle/DataFixtures/ORM/nonexistent.yml',
        ]);
    }

    /**
     * Use nelmio/alice with PURGE_MODE_TRUNCATE.
     *
     * @depends testLoadFixturesFiles
     */
    public function testLoadFixturesFilesWithPurgeModeTruncate(): void
    {
        // Load initial fixtures
        $this->testLoadFixturesFiles();

        $users = $this->userRepository->findAll();

        // There are 10 users in the database
        $this->assertSame(
            10,
            count($users)
        );

        $this->databaseTool->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        // Load fixtures with append = true
        $fixtures = $this->databaseTool->loadAliceFixture([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ], true);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $users = $this->userRepository->findAll();

        // There are only 10 users in the database
        $this->assertSame(
            10,
            count($users)
        );

        // Auto-increment hasn't been altered, so ids start from 11
        $id = 11;
        /** @var User $user */
        foreach ($fixtures as $user) {
            $this->assertSame($id++, $user->getId());
        }
    }

    /**
     * Use nelmio/alice with full path to the file.
     */
    public function testLoadFixturesFilesPaths(): void
    {
        $fixtures = $this->databaseTool->loadAliceFixture([
            static::$kernel->locateResource(
                '@AcmeBundle/DataFixtures/ORM/user.yml'
            ),
        ]);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        /** @var User $user1 */
        $user1 = $fixtures['id1'];

        $this->assertIsString($user1->getUsername());
        $this->assertTrue($user1->getEnabled());

        $users = $this->userRepository->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 1,
            ])
        ;

        $this->assertInstanceOf(User::class, $user);

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice with full path to the file without calling locateResource().
     */
    public function testLoadFixturesFilesPathsWithoutLocateResource(): void
    {
        $fixtures = $this->databaseTool->loadAliceFixture([
            __DIR__.'/../App/DataFixtures/ORM/user.yml',
        ]);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $users = $this->userRepository->findAll();

        $this->assertSame(
            10,
            count($users)
        );
    }

    /**
     * Load nonexistent file with full path.
     */
    public function testLoadNonexistentFixturesFilesPaths(): void
    {
        $path = ['/nonexistent.yml'];

        $this->expectException(InvalidArgumentException::class);

        $this->databaseTool->loadAliceFixture($path);
    }
}
