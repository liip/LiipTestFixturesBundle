# Upgrade guide from 1.x to 2.x

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 1.x to the 2.x version:

- Remove `FixturesTrait`:
    - Access through the service `liip_test_fixtures.services.database_tool_collection` instead
    - Use `loadAliceFixture(…)` instead of `loadFixtureFiles(…)`
    - `loadFixtures()` and `loadFixtureFiles()` only accept 2 arguments, here are the old arguments and the new way:
        - 3rd argument `$omName`: call `$databaseTool->setObjectManagerName($omName);` instead
        - 4th argument `$registryName`: call `$databaseTool->get(null, $registryName);` instead
        - 5th argument `$purgeMode`: call `$databaseTool->setPurgeMode($purgeMode);` instead
- Deprecated the `@DisableDatabaseCache` annotation, use `$this->databaseTool->setDatabaseCacheEnabled(false);` instead,
  and don't forget to 

### Tested based on KernelTestCase

Old code:

```php
<?php

use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConfigTest extends KernelTestCase
{
    use FixturesTrait;
    
    public function testLoadFixtures(): void
    {
        $fixtures = $this->loadFixtureFiles([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ]);
        
        // …
    }
}
```

New code :

```php
<?php

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConfigTest extends KernelTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;
    
    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
    }
    
    public function testLoadFixtures(): void
    {
        $fixtures = $this->databaseTool->loadAliceFixture([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
        ]);
        
        // …
    }
}
```

### Tested based on WebTestCase

Old code:

```php
<?php

use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    use FixturesTrait;

    private $testClient = null;

    public function setUp(): void
    {
        $this->testClient = static::makeClient();
        $this->testClient->getContainer();
    }
    
    public function testUsers()
    {
        $this->loadFixtures([
            'Acme\DataFixtures\ORM\LoadUserData',
        ]);

        $crawler = $this->testClient->request('GET', '/');
        
        // …
    }
}
```

New code :

```php
<?php

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigTest extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;
    
    private $testClient = null;
    
    public function setUp(): void
    {
        $this->testClient = static::makeClient();
        $this->databaseTool = $this->testClient->getContainer()->get(DatabaseToolCollection::class)->get();
    }
    
    public function testUsers()
    {
        $this->databaseTool->loadFixtures([
            'Acme\DataFixtures\ORM\LoadUserData',
        ]);

        $crawler = $this->testClient->request('GET', '/');
        
        // …
    }
}
```
