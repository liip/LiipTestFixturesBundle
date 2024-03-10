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

use Liip\Acme\Tests\AppConfigEvents\AppConfigEventsKernel;
use Liip\Acme\Tests\AppConfigEvents\EventListener\FixturesSubscriber;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseTools\ORMSqliteDatabaseTool;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/AppConfig/AppConfigEventsKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigEventsTest extends KernelTestCase
{
    use ContainerProvider;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    /**
     * Check that events have been registered, they don't do anything but will
     * be called during tests and that ensure that the examples are working.
     */
    public function testLoadEmptyFixturesAndCheckEvents(): void
    {
        $databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get();

        $this->assertInstanceOf(ORMSqliteDatabaseTool::class, $databaseTool);

        $fixtures = $databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $eventDispatcher = $this->getTestContainer()->get('event_dispatcher');

        $event = $eventDispatcher->getListeners(LiipTestFixturesEvents::POST_FIXTURE_SETUP);
        $this->assertSame('postFixtureSetup', $event[0][1]);
    }

    /**
     * Check that the event is called.
     */
    public function testLoadEmptyFixturesAndCheckEventsAreCalled(): void
    {
        $eventName = LiipTestFixturesEvents::POST_FIXTURE_SETUP;
        $methodName = 'postFixtureSetup';
        $numberOfInvocations = 1;

        /** @var AbstractDatabaseTool $databaseTool */
        $databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get();

        $this->assertInstanceOf(ORMSqliteDatabaseTool::class, $databaseTool);

        // Create the mock and declare that the method must be called (or not)
        $mock = $this->getMockBuilder(FixturesSubscriber::class)->getMock();

        $mock->expects($this->exactly($numberOfInvocations))
            ->method($methodName)
        ;

        // Register to the event
        $eventDispatcher = $this->getTestContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            $eventName,
            [$mock, $methodName]
        );

        // By loading fixtures, the events will be called (or not)
        $fixtures = $databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    protected static function getKernelClass(): string
    {
        return AppConfigEventsKernel::class;
    }
}
