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

namespace Liip\Acme\Tests\App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'app_users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private string $name;

    #[ORM\Column(nullable: true)]
    private string $salt;

    #[ORM\Column(nullable: true)]
    private string $email;

    #[ORM\Column(nullable: true)]
    private ?string $dummyText = null;

    public function __construct()
    {
        $this->salt = sha1(
            // http://php.net/manual/fr/function.openssl-random-pseudo-bytes.php
            bin2hex(openssl_random_pseudo_bytes(100))
        );
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setSalt(string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function setDummyText(?string $dummyText): self
    {
        $this->dummyText = $dummyText;

        return $this;
    }

    public function getDummyText(): ?string
    {
        return $this->dummyText;
    }
}
