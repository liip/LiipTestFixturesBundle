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
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigSqliteTwoConnections\AppConfigSqliteTwoConnectionsKernel;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

// BC, needed by "theofidry/alice-data-fixtures: <1.3" not compatible with "doctrine/persistence: ^2.0"
if (interface_exists('\Doctrine\Persistence\ObjectManager')
    && !interface_exists('\Doctrine\Common\Persistence\ObjectManager')) {
    class_alias('\Doctrine\Persistence\ObjectManager', '\Doctrine\Common\Persistence\ObjectManager');
}

/**
 * Test MySQL database with 2 entity managers and connections.
 *
 * The following tests require a connection to a MySQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/ConfigSqliteTwoConnectionsTest/config.yml file and
 * add “--exclude-group ""” when running PHPUnit.
 *
 * Use Tests/ConfigSqliteTwoConnectionsTest/ConfigSqliteTwoConnectionsTestKernel.php
 * instead of Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @IgnoreAnnotation("group")
 *
 * @internal
 */
class ConfigSqliteTwoConnectionsTest extends KernelTestCase
{
    use ContainerProvider;

    /** @var AbstractDatabaseTool */
    protected $databaseToolDefault;
    /** @var AbstractDatabaseTool */
    protected $databaseToolCustom;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->databaseToolDefault = $this->getTestContainer()->get(DatabaseToolCollection::class)->get('default');
        $this->assertInstanceOf(ORMDatabaseTool::class, $this->databaseToolDefault);

        $this->databaseToolCustom = $this->getTestContainer()->get(DatabaseToolCollection::class)->get('custom', 'custom');
        $this->assertInstanceOf(ORMDatabaseTool::class, $this->databaseToolCustom);
    }

    /**
     * Data fixtures.
     *
     * @group mysql
     */
    public function testLoadEmptyFixtures(): void
    {
        $fixturesDefault = $this->databaseToolDefault->loadFixtures([]);
        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixturesDefault
        );

        $fixturesCustom = $this->databaseToolCustom->loadFixtures([]);
        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixturesCustom
        );
    }

    /**
     * @group mysql
     */
    public function testLoadFixtures(int $firstUserId = 1): void
    {
        $this->databaseToolDefault->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $userRepositoryDefault = $this->getTestContainer()->get('doctrine')
            ->getRepository(User::class, 'default')
        ;

        // Load data from database
        /** @var User $user */
        $user = $userRepositoryDefault
            ->findOneBy([
                'email' => 'foo@bar.com',
            ])
        ;

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->databaseToolCustom->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        // Load data from the other database
        $userRepositoryCustom = $this->getTestContainer()->get('doctrine')
            ->getRepository(User::class, 'custom')
        ;

        /** @var User $user */
        $user = $userRepositoryCustom
            ->findOneBy([
                'id' => 1,
            ])
        ;

        $this->assertSame(
            'hey',
            $user->getName()
        );
    }

    protected static function getKernelClass(): string
    {
        return AppConfigSqliteTwoConnectionsKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->userRepositoryDefault);
        unset($this->databaseToolDefault);
        unset($this->databaseToolCustom);
    }
}
