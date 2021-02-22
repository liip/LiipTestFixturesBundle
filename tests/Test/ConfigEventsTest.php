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
use Liip\Acme\Tests\AppConfigEvents\AppConfigEventsKernel;
use Liip\Acme\Tests\AppConfigEvents\EventListener\FixturesSubscriber;
use Liip\Acme\Tests\Traits\ContainerProvider;
use Liip\TestFixturesBundle\Annotations\DisableDatabaseCache;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/AppConfig/AppConfigEventsKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @IgnoreAnnotation("dataProvider")
 */
class ConfigEventsTest extends KernelTestCase
{
    use ContainerProvider;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    protected static function getKernelClass(): string
    {
        return AppConfigEventsKernel::class;
    }

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->databaseTool = $this->getTestContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * Check that events have been registered, they don't do anything but will
     * be called during tests and that ensure that the examples are working.
     */
    public function testLoadEmptyFixturesAndCheckEvents(): void
    {
        $fixtures = $this->databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $eventDispatcher = $this->getTestContainer()->get('event_dispatcher');

        $event = $eventDispatcher->getListeners(LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE);
        $this->assertSame('preFixtureBackupRestore', $event[0][1]);

        $event = $eventDispatcher->getListeners(LiipTestFixturesEvents::POST_FIXTURE_SETUP);
        $this->assertSame('postFixtureSetup', $event[0][1]);

        $event = $eventDispatcher->getListeners(LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE);
        $this->assertSame('postFixtureBackupRestore', $event[0][1]);

        $event = $eventDispatcher->getListeners(LiipTestFixturesEvents::PRE_REFERENCE_SAVE);
        $this->assertSame('preReferenceSave', $event[0][1]);

        $event = $eventDispatcher->getListeners(LiipTestFixturesEvents::POST_REFERENCE_SAVE);
        $this->assertSame('postReferenceSave', $event[0][1]);
    }

    /**
     * Check that events are called.
     *
     * We disable the cache to ensure that all the code is executed.
     *
     * @dataProvider fixturesEventsProvider
     */
    public function testLoadEmptyFixturesAndCheckEventsAreCalled(string $eventName, string $methodName, int $numberOfInvocations): void
    {
        // Create the mock and declare that the method must be called (or not)
        $mock = $this->getMockBuilder(FixturesSubscriber::class)->getMock();

        $mock->expects($this->exactly($numberOfInvocations))
            ->method($methodName);

        // Register to the event
        $eventDispatcher = $this->getTestContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            $eventName,
            [$mock, $methodName]
        );

        // By loading fixtures, the events will be called (or not)
        $fixtures = $this->databaseTool->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    /**
     * We disable the cache to ensure that other events are called.
     *
     * @DisableDatabaseCache()
     *
     * @dataProvider fixturesEventsProvider
     */
    public function testLoadEmptyFixturesAndCheckEventsAreCalledWithoutCache(string $eventName, string $methodName, int $numberOfInvocations): void
    {
        // Swap 0 → 1 and 1 → 0
        $numberOfInvocations = (int) (!$numberOfInvocations);

        $this->testLoadEmptyFixturesAndCheckEventsAreCalled($eventName, $methodName, $numberOfInvocations);
    }

    public function fixturesEventsProvider(): array {
        return [
            [LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE, 'preFixtureBackupRestore', 1],
            [LiipTestFixturesEvents::POST_FIXTURE_SETUP, 'postFixtureSetup', 0],
            [LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE, 'postFixtureBackupRestore', 1],
            [LiipTestFixturesEvents::PRE_REFERENCE_SAVE, 'preReferenceSave', 0],
            [LiipTestFixturesEvents::POST_REFERENCE_SAVE, 'postReferenceSave', 0],
        ];
    }
}
