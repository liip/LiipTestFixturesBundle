<?php

declare(strict_types=1);

namespace Liip\TestFixturesBundle\Services;

use Doctrine\Bundle\MongoDBBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Loader;
use Liip\TestFixturesBundle\FixturesLoaderFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MongoDBFixturesLoaderFactory implements FixturesLoaderFactoryInterface
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
