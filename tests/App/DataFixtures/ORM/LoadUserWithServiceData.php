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
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\App\Service\DummyService;

/**
 * @see LoadDependentUserWithServiceData::getDependencies()
 */
class LoadUserWithServiceData extends AbstractFixture
{
    /** @var DummyService */
    private $dummyService;

    public function __construct(DummyService $dummyService)
    {
        $this->dummyService = $dummyService;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setId(1);
        $user->setName('foo bar');
        $user->setEmail('foo@bar.com');
        $user->setDummyText($this->dummyService->getText());

        $manager->persist($user);
        $manager->flush();

        $this->addReference('serviceUser', $user);

        $user = clone $this->getReference('serviceUser');

        $user->setId(2);

        $manager->persist($user);
        $manager->flush();
    }
}
