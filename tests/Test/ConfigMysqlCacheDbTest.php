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
use Liip\Acme\Tests\AppConfigMysqlCacheDb\AppConfigMysqlKernelCacheDb;

/**
 * Test MySQL database with database caching enabled.
 *
 * The following tests require a connection to a MySQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigMysql/config.yml file and
 * add “--exclude-group ""” when running PHPUnit.
 *
 * Use Tests/AppConfigMysql/AppConfigMysqlKernelCacheDb.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @IgnoreAnnotation("group")
 */
class ConfigMysqlCacheDbTest extends ConfigMysqlTest
{
    protected static function getKernelClass(): string
    {
        return AppConfigMysqlKernelCacheDb::class;
    }

    /**
     * @group mysql
     */
    public function testLoadFixturesAndCheckBackup(): void
    {
        $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        // Load data from database
        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

        // Check that all User have been saved to database
        $this->assertCount(
            2,
            $users
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user1 */
        $user1 = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertSame(
            'foo@bar.com',
            $user1->getEmail()
        );

        $this->assertTrue(
            $user1->getEnabled()
        );

        // Store salt for later use
        $salt = $user1->getSalt();

        // Clean database
        $this->loadFixtures();

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

        // Check that all User have been removed from database
        $this->assertCount(
            0,
            $users
        );

        // Load fixtures again
        $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $users = $em->getRepository('LiipAcme:User')
            ->findAll();

        // Check that all User have been loaded again in database
        $this->assertCount(
            2,
            $users
        );

        $user1 = $em->getRepository('LiipAcme:User')
            ->findOneBy([
                'id' => 1,
            ]);

        // Salt is a random string, if it's the same as before it means that the backup has been saved and loaded
        // successfully
        $this->assertSame(
            $salt,
            $user1->getSalt()
        );
    }

    /**
     * @group mysql
     */
    public function testLoadFixturesCheckReferences(): void
    {
        $referenceRepository = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ])->getReferenceRepository();

        $this->assertCount(1, $referenceRepository->getReferences());

        $referenceRepository = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData',
        ])->getReferenceRepository();

        $this->assertCount(2, $referenceRepository->getReferences());
    }
}
