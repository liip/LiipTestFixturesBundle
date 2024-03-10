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

namespace Liip\Acme\Tests\AppConfigEvents\EventListener;

use Liip\TestFixturesBundle\Event\FixtureEvent;
use Liip\TestFixturesBundle\LiipTestFixturesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixturesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LiipTestFixturesEvents::POST_FIXTURE_SETUP => 'postFixtureSetup',
        ];
    }

    public function postFixtureSetup(FixtureEvent $fixtureEvent): void
    {
        // There are no parameters
        // your code
    }
}
