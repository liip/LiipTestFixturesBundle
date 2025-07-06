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

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
interface DatabaseBackupInterface
{
    /**
     * @param list<\Doctrine\ORM\Mapping\ClassMetadata<object>> $metadatas
     * @param list<string>                                      $classNames
     */
    public function init(array $metadatas, array $classNames, bool $append = false): void;

    public function getBackupFilePath(): string;

    public function isBackupActual(): bool;

    public function backup(AbstractExecutor $executor): void;

    /**
     * @param list<string> $excludedTables
     */
    public function restore(AbstractExecutor $executor, array $excludedTables = []): void;
}
