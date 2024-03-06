<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/TestFixturesBundle
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\Acme\Tests\AppConfigMongodb\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: "string")]
    private string $name;

    #[ODM\Field(type: "string")]
    private string $salt;

    #[ODM\Field(type: "string")]
    private string $email;

    #[ODM\Field(type: "string")]
    private ?string $dummyText = null;

    public function __construct()
    {
        $this->salt = sha1(
            // http://php.net/manual/fr/function.openssl-random-pseudo-bytes.php
            bin2hex(openssl_random_pseudo_bytes(100))
        );
    }

    public function getId(): string
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
