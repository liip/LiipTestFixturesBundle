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
use Doctrine\Persistence\ObjectManager;
use Liip\Acme\Tests\App\Entity\Setting;

class LoadSettingData extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $setting1 = new Setting('name', 'value');

        $manager->persist($setting1);

        $setting2 = new Setting('name_foo', 'value_bar');

        $manager->persist($setting2);

        $manager->flush();
    }
}
