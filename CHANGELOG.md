# Changelog

## 2.0.0

- Deprecated `FixturesTrait`:
  - Access through the service `liip_test_fixtures.services.database_tool_collection` instead
  - Use `loadAliceFixture(…)` instead of `loadFixtureFiles(…)`
- Deprecated the `@DisableDatabaseCache` annotation, use `$this->databaseTool->setDatabaseCacheEnabled(false);` instead

Old code:

```php
<?php

use Liip\TestFixturesBundle\Test\FixturesTrait; 

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

## 1.x.0 (TBA)

- Deprecated `FixturesTrait` #26

## 1.1.0

- Added parameter `liip_test_fixtures.keep_database_and_schema` to avoid issue with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle)

## 1.0.0 (2019-06-11)

Initial release, code extracted from [LiipFunctionalTestBundle](https://github.com/liip/LiipFunctionalTestBundle)
