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

use Doctrine\Persistence\ObjectManager;

class PostFixtureSetupEvent extends FixtureEvent
{
    private ObjectManager $manager;

    public function __construct(
        ObjectManager $manager,
    ) {
        $this->manager = $manager;
    }

    public function getManager(): ObjectManager
    {
        return $this->manager;
    }
}
