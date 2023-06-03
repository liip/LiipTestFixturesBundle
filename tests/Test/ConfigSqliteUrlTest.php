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

// BC, needed by "theofidry/alice-data-fixtures: <1.3" not compatible with "doctrine/persistence: ^2.0"
if (interface_exists('\Doctrine\Persistence\ObjectManager')
    && !interface_exists('\Doctrine\Common\Persistence\ObjectManager')) {
    class_alias('\Doctrine\Persistence\ObjectManager', '\Doctrine\Common\Persistence\ObjectManager');
}

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Liip\Acme\Tests\AppConfigSqliteUrl\AppConfigSqliteUrlKernel;

/**
 * Run SQLite tests by using an URL for Doctrine.
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 *
 * @IgnoreAnnotation("depends")
 * @IgnoreAnnotation("expectedException")
 *
 * @internal
 */
class ConfigSqliteUrlTest extends ConfigSqliteTest
{
    public static function getKernelClass(): string
    {
        return AppConfigSqliteUrlKernel::class;
    }
}
