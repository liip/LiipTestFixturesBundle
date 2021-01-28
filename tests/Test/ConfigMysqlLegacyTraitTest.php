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
use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigMysql\AppConfigMysqlKernel;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

// BC, needed by "theofidry/alice-data-fixtures: <1.3" not compatible with "doctrine/persistence: ^2.0"
if (interface_exists('\Doctrine\Persistence\ObjectManager') &&
    !interface_exists('\Doctrine\Common\Persistence\ObjectManager')) {
    class_alias('\Doctrine\Persistence\ObjectManager', '\Doctrine\Common\Persistence\ObjectManager');
}

/**
 * Test MySQL database with legacy trait.
 *
 * The following tests require a connection to a MySQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigMysql/config.yml file and
 * add “--exclude-group ""” when running PHPUnit.
 *
 * Use Tests/AppConfigMysql/AppConfigMysqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @IgnoreAnnotation("group")
 * @deprecated
 */
class ConfigMysqlLegacyTraitTest extends KernelTestCase
{
    use FixturesTrait;

    /** @var ObjectRepository */
    protected $userRepository;

    protected static function getKernelClass(): string
    {
        return AppConfigMysqlKernel::class;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->getContainer()->get('doctrine')
            ->getRepository('LiipAcme:User');
    }

    /**
     * @group mysql
     */
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

        // Load data from database
        /** @var User $user */
        $user = $this->userRepository
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

    /**
     * Use nelmio/alice.
     *
     * @group mysql
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

        $users = $this->userRepository->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertInstanceOf(User::class, $user);

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $this->userRepository
            ->findOneBy([
                'id' => 10,
            ]);

        $this->assertTrue(
            $user->getEnabled()
        );
    }
}
