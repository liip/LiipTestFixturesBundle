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

use Doctrine\Common\Annotations\Reader;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
final class DatabaseToolCollection
{
    private $container;

    private $annotationReader;

    /**
     * @var AbstractDatabaseTool[][]
     */
    private $items = [];

    public function __construct(ContainerInterface $container, Reader $annotationReader)
    {
        $this->container = $container;
        $this->annotationReader = $annotationReader;
    }

    public function add(AbstractDatabaseTool $databaseTool): void
    {
        $this->items[$databaseTool->getType()][$databaseTool->getDriverName()] = $databaseTool;
    }

    public function get($omName = null, $registryName = 'doctrine', int $purgeMode = null): AbstractDatabaseTool
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);
        $driverName = ('ORM' === $registry->getName()) ? \get_class($registry->getConnection()->getDatabasePlatform()) : 'default';

        $databaseTool = isset($this->items[$registry->getName()][$driverName])
            ? $this->items[$registry->getName()][$driverName]
            : $this->items[$registry->getName()]['default'];

        $databaseTool->setRegistry($registry);
        $databaseTool->setObjectManagerName($omName);
        $databaseTool->setPurgeMode($purgeMode);

        return $databaseTool;
    }
}
