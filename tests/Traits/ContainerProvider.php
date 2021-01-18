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

namespace Liip\Acme\Tests\Traits;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Used by Symfony < 4.0 because self::$container doesn't exist
 */
trait ContainerProvider
{
    public function getTestContainer(): ContainerInterface
    {
        if (property_exists($this, 'container')) {
            return self::$container;
        }

        return $this->getContainer();
    }
}
