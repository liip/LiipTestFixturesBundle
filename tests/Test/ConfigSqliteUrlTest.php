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

use Liip\Acme\Tests\AppConfigSqliteUrl\AppConfigSqliteUrlKernel;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Run SQLite tests by using an URL for Doctrine.
 *
 * @internal
 */
#[PreserveGlobalState(false)]
class ConfigSqliteUrlTest extends ConfigSqliteTest
{
    public static function getKernelClass(): string
    {
        return AppConfigSqliteUrlKernel::class;
    }
}
