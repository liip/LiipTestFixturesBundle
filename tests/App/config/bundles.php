<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['all' => true],
    Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle::class => ['all' => true],
    Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle::class => ['all' => true],
    Liip\TestFixturesBundle\LiipTestFixturesBundle::class => ['all' => true],
    Liip\Acme\Tests\App\AcmeBundle::class => ['all' => true],
    Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle::class => ['phpcr' => true],
];
