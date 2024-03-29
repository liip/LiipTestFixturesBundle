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

namespace Liip\TestFixturesBundle\Services\DatabaseBackup;

use Liip\TestFixturesBundle\FixturesLoaderFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
abstract class AbstractDatabaseBackup implements DatabaseBackupInterface
{
    protected $container;

    protected $fixturesLoaderFactory;

    /**
     * @var array
     */
    protected $metadatas;

    /**
     * The fixture classnames.
     *
     * @var array
     */
    protected $classNames = [];

    public function __construct(ContainerInterface $container, FixturesLoaderFactoryInterface $fixturesLoaderFactory)
    {
        $this->container = $container;
        $this->fixturesLoaderFactory = $fixturesLoaderFactory;
    }

    public function init(array $metadatas, array $classNames, bool $append = false): void
    {
        $this->metadatas = $metadatas;

        if ($append) {
            $this->classNames = array_merge($this->classNames, $classNames);
        } else {
            $this->classNames = $classNames;
        }
    }

    /**
     * Determine if the Fixtures that define a database backup have been
     * modified since the backup was made.
     *
     * @param string $backup The fixture backup database file path
     *
     * @return bool TRUE if the backup was made since the modifications to the
     *              fixtures; FALSE otherwise
     */
    protected function isBackupUpToDate(string $backup): bool
    {
        $backupLastModifiedDateTime = \DateTime::createFromFormat('U', (string) filemtime($backup));

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($this->classNames);

        // Use loader in order to fetch all the dependencies fixtures.
        foreach ($loader->getFixtures() as $className) {
            $fixtureLastModifiedDateTime = $this->getFixtureLastModified($className);
            if ($backupLastModifiedDateTime < $fixtureLastModifiedDateTime) {
                return false;
            }
        }

        return true;
    }

    /**
     * This function finds the time when the data blocks of a class definition
     * file were being written to, that is, the time when the content of the
     * file was changed.
     *
     * @param string $class The fully qualified class name of the fixture class to
     *                      check modification date on
     */
    protected function getFixtureLastModified($class): ?\DateTime
    {
        $lastModifiedDateTime = null;

        $reflClass = new \ReflectionClass($class);
        $classFileName = $reflClass->getFileName();

        if (file_exists($classFileName)) {
            $lastModifiedDateTime = new \DateTime();
            $lastModifiedDateTime->setTimestamp(filemtime($classFileName));
        }

        return $lastModifiedDateTime;
    }
}
