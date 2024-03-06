<?php

declare(strict_types=1);

namespace Liip\TestFixturesBundle;

use Doctrine\Common\DataFixtures\Loader;

interface FixturesLoaderFactoryInterface
{
    /**
     * Retrieve Doctrine DataFixtures loader.
     */
    public function getFixtureLoader(array $classNames): Loader;

}
