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

namespace Liip\Acme\Tests\App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Liip\Acme\Tests\App\Entity\User;

/**
 * @see \Liip\Acme\Tests\Test\ConfigSqliteTest::loadAllFixtures()
 */
class LoadUserDataInGroup extends AbstractFixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setId(1);
        $user->setName('foo bar');
        $user->setEmail('foo@bar.com');

        $manager->persist($user);
        $manager->flush();

        $this->addReference('groupUser', $user);

        $user = clone $this->getReference('groupUser');

        $user->setId(2);

        $manager->persist($user);
        $manager->flush();

        $user = clone $this->getReference('groupUser');

        $user->setId(3);

        $manager->persist($user);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['myGroup'];
    }
}
