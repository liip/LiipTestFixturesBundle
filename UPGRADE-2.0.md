# Upgrade guide from 1.x to 2.x

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 1.x to the 2.x version:

- Removed `FixturesTrait`:
    - Access through the service `DatabaseToolCollection::class` instead
    - Use `loadAliceFixture(…)` instead of `loadFixtureFiles(…)`
    - `loadFixtures()` and `loadFixtureFiles()` only accept 2 arguments, here are the old arguments and the new way to use them:
      - 3rd argument `$omName`:
        - call `self::$container->get(DatabaseToolCollection::class)->get($omName)` instead
      - 4th argument `$registryName`:
        - call `self::$container->get(DatabaseToolCollection::class)->get(null, $registryName)` instead
      - 5th argument `$purgeMode`:
        - if you need to use that option only once: call `$databaseTool->withPurgeMode($purgeMode)->load…;`
        - if you need that option in all of your tests: call the setter `$databaseTool->setPurgeMode($purgeMode);` before loading fixtures
- Removed the `@DisableDatabaseCache` annotation:
    - call `$databaseTool->withDatabaseCacheEnabled(false)->load…;` to use it on the fly
    - or `$this->databaseTool->setDatabaseCacheEnabled(false);` to change it globally

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
