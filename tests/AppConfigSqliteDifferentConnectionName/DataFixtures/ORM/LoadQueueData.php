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

namespace Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Liip\Acme\Tests\AppConfigSqliteDifferentConnectionName\Entity\Queue;

class LoadQueueData extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $queue1 = new Queue();
        $queue1->setId(1);
        $queue1->setPriority(100);
        $queue1->setJobContext(json_encode([
            'className' => 'someClass1',
        ]));

        $manager->persist($queue1);

        $queue2 = new Queue();
        $queue2->setId(2);
        $queue2->setPriority(200);
        $queue2->setJobContext(json_encode([
            'className' => 'someClass2',
        ]));

        $manager->persist($queue2);
        $manager->flush();
    }
}
