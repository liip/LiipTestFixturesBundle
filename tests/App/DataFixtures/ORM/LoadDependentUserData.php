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

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Liip\Acme\Tests\App\Entity\User;

class LoadDependentUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setId(3);
        $user->setName('alice bar');
        $user->setEmail('alice@bar.com');

        $manager->persist($user);
        $manager->flush();

        $user = new User();
        $user->setId(4);
        $user->setName('eve bar');
        $user->setEmail('eve@bar.com');

        $manager->persist($user);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
            LoadSettingData::class,
        ];
    }
}
