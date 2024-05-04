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

namespace Liip\TestFixturesBundle\Services;

use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
final class DatabaseToolCollection
{
    private ContainerInterface $container;

    /**
     * @var AbstractDatabaseTool[][]
     */
    private array $items = [];

    public function __construct(ContainerInterface $container, mixed $annotationReader = null)
    {
        $this->container = $container;

        if (null !== $annotationReader) {
            throw new \RuntimeException(sprintf('Passing a second argument to the "%s" constructor is not supported since liip/test-fixtures-bundle 3.0.', self::class));
        }
    }

    public function add(AbstractDatabaseTool $databaseTool): void
    {
        $driverName = self::normalizeDriverName($databaseTool->getDriverName());

        $this->items[$databaseTool->getType()][$driverName] = $databaseTool;
    }

    public function get($omName = null, $registryName = 'doctrine', ?int $purgeMode = null): AbstractDatabaseTool
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);
        $driverName = ('ORM' === $registry->getName()) ? \get_class($registry->getConnection()->getDatabasePlatform()) : 'default';

        $driverName = self::normalizeDriverName($driverName);

        $databaseTool = $this->items[$registry->getName()][$driverName] ?? $this->items[$registry->getName()]['default'];

        $databaseTool->setRegistry($registry);
        $databaseTool->setObjectManagerName($omName);
        $databaseTool->setPurgeMode($purgeMode);

        return $databaseTool;
    }

    /**
     * On doctrine/dbal ^4.0, the class is named `SQLitePlatform`.
     * On doctrine/dbal < 4.0, the class is named `SqlitePlatform`.
     */
    private static function normalizeDriverName(string $driverName): string
    {
        if ('Doctrine\DBAL\Platforms\SqlitePlatform' === $driverName) {
            return 'Doctrine\DBAL\Platforms\SQLitePlatform';
        }

        return $driverName;
    }
}
