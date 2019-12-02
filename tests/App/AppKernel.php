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

namespace Liip\Acme\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use Liip\TestFixturesBundle\LiipTestFixturesBundle;
use Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

abstract class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),
            new LiipTestFixturesBundle(),
            new AcmeBundle(),
            new NelmioAliceBundle(),
            new FidryAliceDataFixturesBundle(),
        ];

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }

    public function getCacheDir()
    {
        return $this->getBaseDir().'cache';
    }

    public function getLogDir()
    {
        return $this->getBaseDir().'log';
    }

    protected function getBaseDir()
    {
        return sys_get_temp_dir().'/LiipTestFixturesBundle/'.(new ReflectionClass($this))->getShortName().'/var/';
    }
}
