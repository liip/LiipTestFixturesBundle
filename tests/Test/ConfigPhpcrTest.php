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
use Doctrine\DBAL\Logging\Connection as DBALLoggingConnection;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\Acme\Tests\AppConfigPhpcr\AppConfigPhpcrKernel;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\PHPCRDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test PHPCR.
 *
 * Use Tests/AppConfigPhpcr/AppConfigMysqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
class ConfigPhpcrTest extends KernelTestCase
{
    use ContainerProvider;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    protected function setUp(): void
    {
        if (!class_exists(DoctrinePHPCRBundle::class)) {
            $this->markTestSkipped('Need doctrine/phpcr-bundle package.');
        }
        if (class_exists(DBALLoggingConnection::class)) {
            $this->markTestSkipped('Jackalope won\'t work if Doctrine\DBAL\Logging\Connection is provided by Doctrine.');
        }

        parent::setUp();

        self::bootKernel([
            'environment' => 'phpcr',
        ]);

        $entityManager = $this->getTestContainer()->get('doctrine')->getManager();

        $this->databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get('default', 'doctrine_phpcr');

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        if (!empty($metadata)) {
            $schemaTool->createSchema($metadata);
        }

        $this->initRepository();
    }

    public function testToolType(): void
    {
        $this->assertInstanceOf(PHPCRDatabaseTool::class, $this->databaseTool);
    }

    public function testLoadFixturesPhPCr(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([
            'Liip\Acme\Tests\AppConfigPhpcr\DataFixtures\PHPCR\LoadTaskData',
        ]);

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

    protected static function getKernelClass(): string
    {
        return AppConfigPhpcrKernel::class;
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
