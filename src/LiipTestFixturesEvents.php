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

namespace Liip\TestFixturesBundle;

use Liip\TestFixturesBundle\Event\FixtureEvent;

final class LiipTestFixturesEvents
{
    /** @see FixtureEvent */
    public const POST_FIXTURE_SETUP = 'liip_test_fixtures.post_fixture_setup';
}
