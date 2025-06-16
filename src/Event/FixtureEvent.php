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

// Compatibility layer to use Contract if Symfony\Contracts\EventDispatcher\Event is not available
use Symfony\Contracts\EventDispatcher\Event;

class FixtureEvent extends Event
{
}

