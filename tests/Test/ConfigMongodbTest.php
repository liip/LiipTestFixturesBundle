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

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Liip\Acme\Tests\AppConfigMongodb\AppConfigMongodbKernel;
use Liip\Acme\Tests\AppConfigMongodb\DataFixtures\MongoDB\LoadUserDataFixture;
use Liip\Acme\Tests\AppConfigMongodb\Document\User;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\MongoDBDatabaseTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test MongoDB.
 *
 * Use Tests/AppConfigMongodb/AppConfigMongodbKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigMongodbTest extends KernelTestCase
{
    use ContainerProvider;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    private DocumentRepository $userRepository;

    protected function setUp(): void
    {
        if (!class_exists(DoctrineMongoDBBundle::class)) {
            $this->markTestSkipped('Need doctrine/mongodb-odm-bundle package.');
        }

        parent::setUp();

        self::bootKernel([
            'environment' => 'mongodb',
        ]);

        $this->userRepository = $this->getTestContainer()->get('doctrine_mongodb')
            ->getRepository(User::class);

        $this->databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get('default', 'doctrine_mongodb');

        $this->assertInstanceOf(MongoDBDatabaseTool::class, $this->databaseTool);
    }

    public function testLoadFixturesMongodb(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            LoadUserDataFixture::class,
        ]);

        $this->assertInstanceOf(
            MongoDBExecutor::class,
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            ProxyReferenceRepository::class,
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

    protected static function getKernelClass(): string
    {
        return AppConfigMongodbKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}
