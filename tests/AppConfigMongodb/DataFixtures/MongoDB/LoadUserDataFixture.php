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

namespace Liip\Acme\Tests\AppConfigMongodb\DataFixtures\MongoDB;

use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;
use Liip\Acme\Tests\AppConfigMongodb\Document\User;

class LoadUserDataFixture extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof DocumentManager) {
            $class = \get_class($manager);

            throw new \RuntimeException("Fixture requires a MongoDB ODM DocumentManager instance, instance of '{$class}' given.");
        }
        $user = new User();
        $user->setName('foo bar');
        $user->setEmail('foo@bar.com');

        $manager->persist($user);
        $manager->flush();

        $this->addReference('user', $user);

        $user = clone $this->getReference('user');

        $manager->persist($user);
        $manager->flush();
    }
}
