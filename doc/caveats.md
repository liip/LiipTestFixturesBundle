# Caveats

## Conflicts

### DAMADoctrineTestBundle

Due to conflicting operations with databases, this bundle can trigger the following error with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle): 

```
Doctrine\DBAL\Driver\PDOException: SQLSTATE[42000]: Syntax error or access violation: 1305 SAVEPOINT DOCTRINE2_SAVEPOINT_2 does not exist
``` 

To avoid this, disable automatic changes to database and schema:

```
liip_test_fixtures:
    keep_database_and_schema: true
```

## [Semantical Error] The annotation "@…" in method …::test…() was never imported

See this [solution](https://github.com/liip/LiipFunctionalTestBundle/blob/901a5126e1e58740656cb816cefb2605d8aa47bb/doc/caveats.md).
