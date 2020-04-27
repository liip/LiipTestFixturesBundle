Examples
========

Fixtures
--------

The bundle's internal tests show several ways to load fixtures:

- [data with fixtures dependencies](../tests/App/DataFixtures/ORM/LoadDependentUserData.php)
- [data with dependency injection](../tests/App/DataFixtures/ORM/LoadUserWithServiceData.php)
- [fixture loading with Alice](../tests/App/DataFixtures/ORM/user.yml)
- custom provider:
  - [fixture to load](../tests/App/DataFixtures/ORM/user_with_custom_provider.yml)
  - [custom provider](../tests/AppConfig/DataFixtures/Faker/Provider/FooProvider.php)
  - [service declaration](../tests/AppConfig/config.yml)

Functional test
---------------

```php
<?php

declare(strict_types=1);

namespace Liip\FooBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Test\FixturesTrait;

class ExampleFunctionalTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * Example using LiipFunctionalBundle the fixture loader.
     */
    public function testUserFooIndex(): void
    {
        // If you need a client, you must create it before loading fixtures because
        // creating the client boots the kernel, which is used by loadFixtures
        $client = $this->createClient();
        $this->loadFixtures(['Liip\FooBundle\Tests\Fixtures\LoadUserData']);

        $crawler = $client->request('GET', '/users/foo');
        
        // …
    }
}
```
