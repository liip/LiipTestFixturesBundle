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

class PostFixtureBackupRestoreEvent extends FixtureEvent
{
    private $backupFilePath;

    public function __construct(string $backupFilePath)
    {
        $this->backupFilePath = $backupFilePath;
    }

    public function getBackupFilePath(): string
    {
        return $this->backupFilePath;
    }
}
