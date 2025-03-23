Database Tests
==============

If you plan on loading fixtures with your tests, make sure you have the
DoctrineFixturesBundle installed and configured first:
[Doctrine Fixtures setup and configuration instructions](http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html#setup-and-configuration)

In case tests require database access make sure that the database is created and
proxies are generated.  For tests that rely on specific database contents,
write fixture classes and call `loadFixtures()` method.
This will replace the database configured in
`config_test.yml` with the specified fixtures. Please note that `loadFixtures()`
will delete the contents from the database before loading the fixtures. That's
why you should use a designated database for tests.

Usage
-----

You'll have to make the following changes in order to use that bundle:

```diff
+use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
+use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
+    /** @var AbstractDatabaseTool */
+    protected $databaseTool;

    public function setUp(): void
    {
        parent::setUp();

+        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
+        // or with Symfony < 5.3
+        // static::bootKernel();
+        // $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
    }

    public function testIndex()
    {
        // If you need a client, you must create it before loading fixtures because
        // creating the client boots the kernel, which is used by loadFixtures
        $client = $this->createClient();

+        // add all your fixtures classes that implement
+        // Doctrine\Common\DataFixtures\FixtureInterface
+        $this->databaseTool->loadFixtures([
+            'Bamarni\MainBundle\DataFixtures\ORM\LoadData',
+            'Me\MyBundle\DataFixtures\ORM\LoadData'
+        ]);

        // you can now run your functional tests with a populated database
        // ...
    }
+
+    protected function tearDown(): void
+    {
+        parent::tearDown();
+        unset($this->databaseTool);
+    }
}
```

Methods
-------

`static::getContainer()->get(DatabaseToolCollection::class)` has a method `get()` to load the default service, it also accepts several arguments:
1. name of the object manager
2. name of the registry, `doctrine` is the default value
3. purge mode with `true` or `false`

`$this->databaseTool` gives access to 3 methods to load fixtures:

- `loadFixtures()`, the first parameter accepts an array of [fixtures](#load-fixtures-), the second argument is optional too and has to be set to true in order to append the fixtures to the existing data 
- `loadAliceFixture()`, the first parameter accepts an array of [Alice fixtures](#loading-fixtures-using-alice-), the second argument is optional too and has to be set to true in order to append the fixtures to the existing data
- `loadAllFixtures()`, it accepts an array of groups, see [load all fixtures](#load-all-fixtures-)

It also give access to other helpers:

- `setDatabaseCacheEnabled()` accept `true` or `false` to disable the cache
  - you can call `$this->databaseTool->withDatabaseCacheEnabled(false)->loadFixtures(…)` to disable the cache on-demand

- `setPurgeMode()` accept `true` or `false` to disable purging the database
  - you can call `$this->databaseTool->withPurgeMode(false)->loadFixtures(…)` to disable the purging on-demand

- `setExcludedDoctrineTables()` accepts an array of table names that will be preserved, see [Exclude some tables](#exclude-some-tables-)


Tips for Fixture Loading Tests
------------------------------

### SQLite ([↑](#methods))

 1. If you want your tests to run against a completely isolated database (which
    is recommended for most functional-tests), you can configure your
    test-environment to use a SQLite-database. This will make your tests run
    faster and will create a fresh, predictable database for every test you run.

    * For symfony 4 : create file if it doesn't exists `config/packages/test/doctrine.yaml`, and if it does append those lines:
        ```yaml
        # config/packages/test/doctrine.yaml
        doctrine:
            dbal:
                driver: pdo_sqlite
                path: "%kernel.cache_dir%/test.db"
                url: null

    NB: If you have an existing Doctrine configuration which uses slaves be sure to separate out the configuration for the slaves. Further detail is provided at the bottom of this README.

 2. In order to run your tests even faster, use LiipFunctionalBundle cached database.
    This will create backups of the initial databases (with all fixtures loaded)
    and re-load them when required.

    **Attention: you need Doctrine >= 2.2 to use this feature.**

    ```yaml
    # sf4: config/packages/test/framework.yaml
    liip_test_fixtures:
        cache_db:
            sqlite: 'Liip\TestFixturesBundle\Services\DatabaseBackup\SqliteDatabaseBackup'
    ```

### Custom database cache services ([↑](#methods))

 To create custom database cache service:

Create cache class, implement `\Liip\TestFixturesBundle\Services\DatabaseBackup\DatabaseBackupInterface` and add it to config

For example:
```yaml
# app/config/config_test.yml
liip_test_fixtures:
    cache_db:
        mysql: 'Liip\TestFixturesBundle\Services\DatabaseBackup\MysqlDatabaseBackup'
        mongodb: 'Liip\TestFixturesBundle\Services\DatabaseBackup\MongodbDatabaseBackup'
        db2: ...
        [Other \Doctrine\DBAL\Platforms\AbstractPlatform name]: ...
```

**Attention: `Liip\TestFixturesBundle\Services\DatabaseBackup\MysqlDatabaseBackup` requires `mysql-client` installed on server.**

**Attention: `Liip\TestFixturesBundle\Services\DatabaseBackup\MongodbDatabaseBackup` requires `mongodb-clients` installed on server.**
 
### Load fixtures ([↑](#methods))

Load your Doctrine fixtures in your tests:

```php
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    /**
     * @var AbstractDatabaseTool
     */
    protected $databaseTool;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function testIndex()
    {
        // If you need a client, you must create it before loading fixtures because
        // creating the client boots the kernel, which is used by loadFixtures
        $client = $this->createClient();

        // add all your fixtures classes that implement
        // Doctrine\Common\DataFixtures\FixtureInterface
        $this->databaseTool->loadFixtures([
            'Bamarni\MainBundle\DataFixtures\ORM\LoadData',
            'Me\MyBundle\DataFixtures\ORM\LoadData'
        ]);

        // you can now run your functional tests with a populated database
        // ...
    }

     protected function tearDown(): void
     {
         parent::tearDown();
         unset($this->databaseTool);
     }
}
```

### Have an empty database without fixtures ([↑](#methods))

If you don't need any fixtures to be loaded and just want to start off with
an empty database (initialized with your schema), you can simply call
`loadFixtures` without any argument.

```php
$this->databaseTool->loadFixtures();
```

### Exclude some tables ([↑](#methods))

Given that you want to exclude some of your doctrine tables from being purged
when loading the fixtures, you can do so by passing an array of tablenames 
to the `setExcludedDoctrineTables` method before loading the fixtures.

```php
$this->databaseTool->setExcludedDoctrineTables(['my_tablename_not_to_be_purged']);
$this->databaseTool->loadFixtures([
    'Me\MyBundle\DataFixtures\ORM\LoadData'
]);
```

### Append data ([↑](#methods))

If you want to append fixtures instead of clean database and load them, you have
to consider use the second parameter $append with value true.

```php
$this->databaseTool->loadFixtures([
    'Me\MyBundle\DataFixtures\ORM\LoadAnotherObjectData',
], true);
```

### Other drivers ([↑](#methods))

This bundle uses Doctrine ORM by default. If you are using another driver just
specify the service id of the registry manager:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    // …

   
    public function setUp(): void
    {
        // …

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get('default', 'doctrine_phpcr');
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}
```

### Load all fixtures ([↑](#methods))

If you need to load all fixtures, you can call `loadAllFixtures`. With the optional argument 
groups, only those fixtures belonging to a group (i.e. using the `FixtureGroupInterface`)
are loaded, like when calling the command `doctrine:fixtures:load --group=...`:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    // …

   public function testIndex()
   {
       // If you need a client, you must create it before loading fixtures because
       // creating the client boots the kernel, which is used by loadFixtures
       $client = $this->createClient();
       $this->databaseTool->loadAllFixtures(['mygroup1', 'mygroup2']);

       // you can now run your functional tests with a populated database
       // ...
   }
}
```

### Loading Fixtures Using Alice ([↑](#methods))

If you would like to setup your fixtures with yml files using [Alice](https://github.com/nelmio/alice),
there is an helper function `loadFixtureFiles`
which takes an array of resources, or paths to yml files, and returns an array of objects.
This method uses the [Theofidry AliceDataFixtures loader](https://github.com/theofidry/AliceDataFixtures#doctrine-orm)
rather than the FunctionalTestBundle's load methods.
You should be aware that there are some difference between the ways these two libraries handle loading.

```php
$fixtures = $this->databaseTool->loadAliceFixture([
    '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
    '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
    __DIR__.'/../../DataFixtures/ORM/YetAnotherObjectData.yml',
]);
```

If you want to clear tables you have the following two ways:
1. Only to remove records of tables;
2. Truncate tables.

The first way is consisted in using the second parameter `$append` with value `false`. It allows you **only** to remove all records of table. Values of auto increment won't be reset. 
```php
$fixtures = $this->databaseTool->loadAliceFixture([
        '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
        '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
        __DIR__.'/../../DataFixtures/ORM/YetAnotherObjectData.yml',
    ],
    false
);
```

The second way is consisted in using the second parameter `$append` with value `false` and the last parameter `$purgeMode` with value `Doctrine\Common\DataFixtures\Purger\ORMPurger::PURGE_MODE_TRUNCATE`. It allows you to remove all records of tables with resetting value of auto increment.

```php
<?php

use Doctrine\Common\DataFixtures\Purger\ORMPurger;

$files = [
     '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
     '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
     __DIR__.'/../../DataFixtures/ORM/YetAnotherObjectData.yml',
 ];
$fixtures = $this->databaseTool->loadAliceFixture($files, false, null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE );
```

### Non-SQLite ([↑](#methods))

The Bundle will not automatically create your schema for you unless you use SQLite
or use `doctrine/orm` >= 2.6.

So you have several options:

- use SQLite driver in tests
- upgrade `doctrine/orm` :
   
   ```bash
   composer require doctrine/orm:^2.6
   ```
- if you prefer to use another database but want your schema/fixtures loaded
automatically, you'll need to do that yourself. For example, you could write a
`setUp()` function in your test, like so:


```php 
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    public function setUp()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        if (!isset($metadatas)) {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }

        $fixtures = [
            'Acme\MyBundle\DataFixtures\ORM\LoadUserData',
        ];
        $this->databaseTool->loadFixtures($fixtures);
    }
//...
}
```

Without something like this in place, you'll have to load the schema into your
test database manually, for your tests to pass.

### Referencing fixtures in tests ([↑](#methods))

In some cases you need to know for example the row ID of an object in order to write a functional test for it, e.g. 
`$crawler = $client->request('GET', "/profiles/$accountId");` but since the `$accountId` keeps changing each test run, you need to figure out its current value. Instead of going via the entity manager repository and querying for the entity, you can use `setReference()/getReference()` from the fixture executor directly, as such:

In your fixtures class:

```php
...
class LoadMemberAccounts extends AbstractFixture 
{
    public function load() 
    {
        $account1 = new MemberAccount();
        $account1->setName('Alpha');
        $this->setReference('account-alpha', $account1);
        ...
```    
and then in the test case setup:
```php
...
    public function setUp()
    {
        $this->fixtures = $this->databaseTool->loadFixtures([
            'AppBundle\Tests\Fixtures\LoadMemberAccounts'
        ])->getReferenceRepository();
    ...
```
and finally, in the test:
```php
        $accountId = $this->fixtures->getReference('account-alpha')->getId();
        $crawler = $client->request('GET', "/profiles/$accountId");
```

Doctrine Slaves and SQLite ([↑](#methods))
----------------------------------------

If your main configuration for Doctrine uses Slaves, you need to ensure that the configuration for your SQLite test environment does not include the slave configuration.

The following error can occur in the case where a Doctrine Slave configuration is included:

    SQLSTATE[HY000]: General error: 1 no such table NameOfTheTable

This may also manifest itself in the command `doctrine:create:schema` doing nothing.

To resolve the issue, it is recommended to configure your Doctrine slaves  specifically for the environments that require them.

« [Configuration](./configuration.md) • [Events](./events.md) »
