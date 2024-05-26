# Caveats

## Conflicts

### DAMADoctrineTestBundle

Due to conflicting operations with databases, this bundle can trigger the following error with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle): 

```
Doctrine\DBAL\Driver\PDOException: SQLSTATE[42000]: Syntax error or access violation: 1305 SAVEPOINT DOCTRINE2_SAVEPOINT_2 does not exist
``` 

To avoid this, disable automatic changes to database and schema:

```
# config/packages/test/liip_fixtures.yaml
liip_test_fixtures:
    keep_database_and_schema: true
```

You'll have to [create database and update schema](./configuration.md#configuration) before running your tests on local environment or CI.

## [Semantical Error] The annotation "@…" in method …::test…() was never imported

See this [solution](https://github.com/liip/LiipFunctionalTestBundle/blob/901a5126e1e58740656cb816cefb2605d8aa47bb/doc/caveats.md).

« [Examples](./examples.md)

## Operation 'AbstractPlatform::getListDatabasesSQL' is not supported by platform

This can be caused by `sentry/sentry-symfony` which decorates the database connection layer.

Disable sentry in the `test` environment to avoid this issue:

```yaml
# config/packages/test/sentry.yaml
sentry:
    tracing:
        enabled: false
```

## You have requested a non-existent service "Liip\TestFixturesBundle\Services\DatabaseToolCollection"

Check that the parameter `framework.test` is enabled:

Symfony 4 and 5:

```yaml
# config/package/test/framework.yaml
framework:
    test: true
```

Symfony 6:

```yaml
# config/package/framework.yaml
when@test:
    framework:
        test: true
```

## LogicException: […] the kernel should only be booted once.

Complete error message:

> LogicException: Booting the kernel before calling "Symfony\Bundle\FrameworkBundle\Test\WebTestCase::createClient()" is not supported, the kernel should only be booted once.

Solution: Call `self::ensureKernelShutdown();` before creating the client.
