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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
abstract class FixturesTestCase extends KernelTestCase
{
    protected function tearDown(): void
    {
        if (!$this->isTestSkipped()) {
            $this->removeBackups();
        }

        parent::tearDown();
    }

    private function removeBackups(): void
    {
        $cacheDir = self::getContainer()->getParameter('kernel.cache_dir');
        $backups = glob($cacheDir.'/test_{mongodb,mysql,postgresql,sqlite}_*', GLOB_BRACE);
        foreach ($backups as $backup) {
            $this->removeFile($backup);
        }
    }

    private function removeFile(string $filename): void
    {
        if (!is_dir($filename)) {
            unlink($filename);

            return;
        }

        $directory = $filename;
        $filenames = scandir($directory);
        foreach ($filenames as $filename) {
            if (\in_array($filename, ['.', '..'], true)) {
                continue;
            }

            $this->removeFile($filename);
        }

        rmdir($directory);
    }

    private function isTestSkipped(): bool
    {
        if (version_compare(\PHPUnit\Runner\Version::id(), '10.0.0', '<')) {
            return \PHPUnit\Runner\BaseTestRunner::STATUS_SKIPPED === $this->getStatus();
        }

        return $this->status()->isSkipped();
    }
}
