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
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\Acme\Tests\App\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserDataInGroup extends AbstractFixture implements FixtureInterface, FixtureGroupInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = new User();
        $user->setId(1);
        $user->setName('foo bar');
        $user->setEmail('foo@bar.com');
        $user->setPassword('12341234');
        $user->setAlgorithm('plaintext');
        $user->setEnabled(true);
        $user->setConfirmationToken(null);

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

    /**
     * @inheritDoc
     */
    public static function getGroups(): array
    {
        return ['myGroup'];
    }
}
