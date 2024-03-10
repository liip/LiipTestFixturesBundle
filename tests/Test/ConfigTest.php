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

use Doctrine\Persistence\ObjectRepository;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfig\AppConfigKernel;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMSqliteDatabaseTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigTest extends KernelTestCase
{
    use ContainerProvider;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;
    /** @var ObjectRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->userRepository = $this->getTestContainer()->get('doctrine')
            ->getRepository(User::class)
        ;

        $this->databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get();

        $this->assertInstanceOf(ORMSqliteDatabaseTool::class, $this->databaseTool);
    }

    /**
     * Load Data Fixtures with custom loader defined in configuration.
     */
    public function testLoadFixturesFilesWithCustomProvider(): void
    {
        // Load default Data Fixtures.
        $fixtures = $this->databaseTool->loadAliceFixture([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ]);

        $this->assertIsArray($fixtures);

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        /** @var User $user */
        $user = $fixtures['id1'];

        // The custom provider has not been used successfully.
        $this->assertStringStartsNotWith(
            'foo',
            $user->getName()
        );

        // Load Data Fixtures with custom loader defined in configuration.
        $fixtures = $this->databaseTool->loadAliceFixture([
            '@AcmeBundle/DataFixtures/ORM/user_with_custom_provider.yml',
        ]);

        /** @var User $user */
        $user = $fixtures['id1'];

        // The custom provider "foo" has been loaded and used successfully.
        $this->assertSame(
            'fooa string',
            $user->getName()
        );
    }

    protected static function getKernelClass(): string
    {
        return AppConfigKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->userRepository, $this->databaseTool);
    }
}
