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

use Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\Acme\Tests\AppConfigPhpcr\AppConfigPhpcrKernel;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zalas\Injector\PHPUnit\Symfony\TestCase\SymfonyTestContainer;
use Zalas\Injector\PHPUnit\TestCase\ServiceContainerTestCase;

/**
 * Test PHPCR.
 *
 * Use Tests/AppConfigPhpcr/AppConfigMysqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @preserveGlobalState disabled
 */
class ConfigPhpcrTest extends KernelTestCase implements ServiceContainerTestCase
{
    use SymfonyTestContainer;

    /**
     * @var EntityManager
     * @inject doctrine
     */
    private $entityManager;

    /**
     * @var DatabaseToolCollection
     * @inject liip_test_fixtures.services.database_tool_collection
     */
    private $databaseToolCollection;

    /**
     * @var AbstractDatabaseTool
     */
    private $databaseTool;

    protected static function getKernelClass(): string
    {
        return AppConfigPhpcrKernel::class;
    }

    public function setUp(): void
    {
        if (!class_exists(DoctrinePHPCRBundle::class)) {
            $this->markTestSkipped('Need doctrine/phpcr-bundle package.');
        }

        parent::setUp();

        $this->assertInstanceOf(DatabaseToolCollection::class, $this->databaseToolCollection);

        $this->databaseTool = $this->databaseToolCollection->get();

        // https://github.com/liip/LiipTestFixturesBundle/blob/master/doc/database.md#non-sqlite
        if (!isset($metadatas)) {
            $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }

        $this->initRepository();
    }

    public function testLoadFixturesPhPCr(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\AppConfigPhpcr\DataFixtures\PHPCR\LoadTaskData',
        ], false, null, 'doctrine_phpcr');

        $this->assertInstanceOf(
            'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );
    }

    /**
     * Define the PHPCR root, used in fixtures.
     */
    private function initRepository(): void
    {
        $kernel = static::$kernel;

        $application = new Application($kernel);

        $command = $application->find('doctrine:phpcr:repository:init');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()]
        );
    }
}
