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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName\AppConfigSqliteDifferentConnectionNameKernel;
use Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName\DataFixtures\ORM\LoadQueueData;
use Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName\Entity\Queue;
use Liip\TestFixturesBundle\Event\PostFixtureSetupEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test SQLite database with entity manager having different name than its connection.
 *
 * @internal
 */
class ConfigSqliteDifferentConnectionNameTest extends ConfigSqliteTest
{
    private const MAIN_ENTITY_MANAGER_NAME = 'different';
    private const ADDITIONAL_ENTITY_MANAGER_NAME = 'additional';

    private const MAIN_CONNECTION_NAME = 'default';
    private const ADDITIONAL_CONNECTION_NAME = 'additional';

    /**
     * @var EntityRepository<User>
     */
    private EntityRepository $queueRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $testContainer = static::getContainer();

        $this->queueRepository = $testContainer->get('doctrine')
            ->getRepository(Queue::class);

        $this->databaseTool = $testContainer->get(DatabaseToolCollection::class)
            ->get(self::MAIN_ENTITY_MANAGER_NAME);
    }

    public function testLoadFixturesForSecondEntityManager(): void
    {
        $this->getDatabaseTool(self::ADDITIONAL_ENTITY_MANAGER_NAME)
            ->loadFixtures([LoadQueueData::class]);

        $queueList = $this->queueRepository->findAll();

        $this->assertCount(2, $queueList);

        /** @var Queue $queue */
        $queue = $this->queueRepository
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertSame(
            '{"className":"someClass1"}',
            $queue->getJobContext()
        );
    }

    /**
     * @return iterable<array{0:string,1:string}>
     */
    public static function objectManagerNameViaEventDataProvider(): iterable
    {
        yield [self::MAIN_ENTITY_MANAGER_NAME, self::MAIN_CONNECTION_NAME];
        yield [self::ADDITIONAL_ENTITY_MANAGER_NAME, self::ADDITIONAL_CONNECTION_NAME];
    }

    #[DataProvider('objectManagerNameViaEventDataProvider')]
    public function testObjectManagerAndConnectionViaEvent(string $onName, string $connectionName): void
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get(EventDispatcherInterface::class);
        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get(ManagerRegistry::class);

        $eventDispatcher->addListener(
            LiipTestFixturesEvents::POST_FIXTURE_SETUP,
            function (PostFixtureSetupEvent $event) use ($registry, $onName, $connectionName) {
                /** @var EntityManagerInterface $entityManager */
                $entityManager = $event->getManager();

                $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

                $this->assertSame(
                    $registry->getManager($onName),
                    $entityManager,
                );

                $this->assertSame(
                    $registry->getConnection($connectionName),
                    $entityManager->getConnection(),
                );
            }
        );

        $this->getDatabaseTool($onName)
            ->loadFixtures();
    }

    public static function getKernelClass(): string
    {
        return AppConfigSqliteDifferentConnectionNameKernel::class;
    }

    private function getDatabaseTool(string $omName): AbstractDatabaseTool
    {
        $testContainer = static::getContainer();

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = $testContainer->get(DatabaseToolCollection::class);

        return $databaseToolCollection->get($omName);
    }
}
