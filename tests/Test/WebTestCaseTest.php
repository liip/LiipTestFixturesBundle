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
use Liip\Acme\Tests\AppConfigSqlite\AppConfigSqliteKernel;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @IgnoreAnnotation("depends")
 * @IgnoreAnnotation("expectedException")
 */
class WebTestCaseTest extends WebTestCase
{
    use FixturesTrait;

    public function setUp(): void
    {
        static::$class = AppConfigSqliteKernel::class;
    }

    public static function getKernelClass()
    {
        return AppConfigSqliteKernel::class;
    }

    public function testLoadEmptyFixtures(): void
    {
        $fixtures = $this->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    public function testLoadFixturesWithoutParameters(): void
    {
        $fixtures = $this->loadFixtures();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    public function testLoadFixtures(): void
    {
        $fixtures = $this->loadFixtures([
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

        /** @var \Liip\Acme\Tests\App\Entity\User $user1 */
        $user1 = $repository->getReference('user');

        $this->assertSame(1, $user1->getId());
        $this->assertSame('foo bar', $user1->getName());
        $this->assertSame('foo@bar.com', $user1->getEmail());
        $this->assertTrue($user1->getEnabled());

        // Load data from database
        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

        // There are 2 users.
        $this->assertSame(
            2,
            count($users)
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    public function testAppendFixtures(): void
    {
        $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->loadFixtures(
            ['Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData'],
            true
        );

        // Load data from database
        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 3,
            ]);

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
        $fixtures = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadDependentUserData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

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
        $fixtures = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadDependentUserWithServiceData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

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
        $fixtures = $this->loadFixtureFiles([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ]);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 10,
            ]);

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Load nonexistent resource.
     */
    public function testLoadNonexistentFixturesFiles(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loadFixtureFiles([
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
        $fixtures = $this->loadFixtureFiles([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ], true, null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $id = 1;
        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        foreach ($fixtures as $user) {
            $this->assertSame($id++, $user->getId());
        }
    }

    /**
     * Use nelmio/alice with full path to the file.
     */
    public function testLoadFixturesFilesPaths(): void
    {
        $fixtures = $this->loadFixtureFiles([
            $this->getContainer()->get('kernel')->locateResource(
                '@AcmeBundle/DataFixtures/ORM/user.yml'
            ),
        ]);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user1 */
        $user1 = $fixtures['id1'];

        $this->assertIsString($user1->getUsername());
        $this->assertTrue($user1->getEnabled());

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice with full path to the file without calling locateResource().
     */
    public function testLoadFixturesFilesPathsWithoutLocateResource(): void
    {
        $fixtures = $this->loadFixtureFiles([
            __DIR__.'/../App/DataFixtures/ORM/user.yml',
        ]);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

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

        $this->expectException(\InvalidArgumentException::class);

        $this->loadFixtureFiles($path);
    }
}
