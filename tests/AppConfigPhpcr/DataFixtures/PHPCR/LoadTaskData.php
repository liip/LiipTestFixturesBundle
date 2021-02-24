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

namespace Liip\Acme\Tests\AppConfigPhpcr\DataFixtures\PHPCR;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Liip\Acme\Tests\AppConfigPhpcr\Document\Task;
use RuntimeException;

class LoadTaskData implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof DocumentManager) {
            $class = get_class($manager);

            throw new RuntimeException("Fixture requires a PHPCR ODM DocumentManager instance, instance of '{$class}' given.");
        }

        $rootTask = $manager->find(null, '/');

        if (!$rootTask) {
            throw new Exception('Could not find / document!');
        }

        $task = new Task();
        $task->setDescription('Finish CMF project');
        $task->setParentDocument($rootTask);

        $manager->persist($task);

        $manager->flush();
    }
}
