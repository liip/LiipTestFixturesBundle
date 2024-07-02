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

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Persistence\ObjectManager;

class PreFixtureBackupRestoreEvent extends FixtureEvent
{
    private $manager;
    private $repository;
    private $backupFilePath;

    public function __construct(
        ObjectManager $manager,
        ReferenceRepository $executor,
        string $backupFilePath
    ) {
        $this->manager = $manager;
        $this->repository = $executor;
        $this->backupFilePath = $backupFilePath;
    }

    public function getManager(): ObjectManager
    {
        return $this->manager;
    }

    public function getRepository(): ReferenceRepository
    {
        return $this->repository;
    }

    public function getBackupFilePath(): string
    {
        return $this->backupFilePath;
    }
}
