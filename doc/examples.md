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
use Zalas\Injector\PHPUnit\Symfony\TestCase\SymfonyTestContainer;
use Zalas\Injector\PHPUnit\TestCase\ServiceContainerTestCase;

class ExampleFunctionalTest extends WebTestCase implements ServiceContainerTestCase
{
    use SymfonyTestContainer;

    /**
     * @var \Liip\TestFixturesBundle\Services\DatabaseToolCollection
     * @inject liip_test_fixtures.services.database_tool_collection
     */
    private $databaseToolCollection;

    /**
     * @var AbstractDatabaseTool
     */
    protected $databaseTool;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = $this->databaseToolCollection->get();
    }

    /**
     * Example using LiipFunctionalBundle the fixture loader.
     */
    public function testUserFooIndex(): void
    {
        // If you need a client, you must create it before loading fixtures because
        // creating the client boots the kernel, which is used by loadFixtures
        $client = $this->createClient();
        $this->databaseTool->loadFixtures(['Liip\FooBundle\Tests\Fixtures\LoadUserData']);

        $crawler = $client->request('GET', '/users/foo');
        
        // …
    }
}
```
