<?php

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\TestFixturesBundle\Services\DatabaseBackup;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
interface DatabaseBackupInterface
{
    public function init(array $metadatas, array $classNames, bool $append = false): void;

    public function getBackupFilePath(): string;

    public function isBackupActual(): bool;

    public function backup(AbstractExecutor $executor): void;

    public function restore(AbstractExecutor $executor, array $excludedTables = []): void;
}
