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

namespace Liip\TestFixturesBundle\Event;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Persistence\ObjectManager;

class ReferenceSaveEvent extends FixtureEvent
{
    private $manager;
    private $executor;
    private $backupFilePath;

    public function __construct(
        ObjectManager $manager,
        AbstractExecutor $executor,
        string $backupFilePath
    ) {
        $this->manager = $manager;
        $this->executor = $executor;
        $this->backupFilePath = $backupFilePath;
    }

    public function getManager(): ObjectManager
    {
        return $this->manager;
    }

    public function getExecutor(): AbstractExecutor
    {
        return $this->executor;
    }

    public function getBackupFilePath(): string
    {
        return $this->backupFilePath;
    }
}
