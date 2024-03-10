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
use Liip\Acme\Tests\App\Service\DummyService;

class LoadDependentUserWithServiceData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var DummyService */
    private $dummyService;

    public function __construct(DummyService $dummyService)
    {
        $this->dummyService = $dummyService;
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = clone $this->getReference('serviceUser');

        $user->setId(3);
        $user->setDummyText($this->dummyService->getText());

        $manager->persist($user);
        $manager->flush();

        $user = clone $this->getReference('serviceUser');

        $user->setId(4);

        $manager->persist($user);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserWithServiceData',
        ];
    }
}
