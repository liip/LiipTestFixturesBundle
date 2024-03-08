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

namespace Liip\TestFixturesBundle\Services;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;

final class SymfonyFixturesLoaderWrapper extends Loader
{
    private Loader $symfonyFixturesLoader;

    public function __construct(Loader $symfonyFixturesLoader)
    {
        $this->symfonyFixturesLoader = $symfonyFixturesLoader;
    }

    public function loadFixturesClass($className): void
    {
        $this->addFixture($this->symfonyFixturesLoader->getFixture($className));
    }

    public function createFixture($class): FixtureInterface
    {
        return $this->symfonyFixturesLoader->getFixture($class);
    }
}
