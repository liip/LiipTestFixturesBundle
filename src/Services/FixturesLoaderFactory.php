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

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
final class FixturesLoaderFactory
{
    private ContainerInterface $container;

    private ?SymfonyFixturesLoader $loader;

    public function __construct(ContainerInterface $container, SymfonyFixturesLoader $loader = null)
    {
        $this->container = $container;
        $this->loader = $loader;
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     */
    public function getFixtureLoader(array $classNames): Loader
    {
        if (null === $this->loader) {
            throw new \BadMethodCallException('doctrine/doctrine-fixtures-bundle must be installed to use this method.');
        }

        $loader = new SymfonyFixturesLoaderWrapper($this->loader);
        foreach ($classNames as $className) {
            $loader->loadFixturesClass($className);
        }

        return $loader;
    }
}
