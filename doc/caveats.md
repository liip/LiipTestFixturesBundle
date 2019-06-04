# Caveats

## Conflicts

### DAMADoctrineTestBundle

Due to conflicting operations with databases, this bundle has compatibility issues with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle). 

This triggers the following error:

```
Doctrine\DBAL\Driver\PDOException: SQLSTATE[42000]: Syntax error or access violation: 1305 SAVEPOINT DOCTRINE2_SAVEPOINT_2 does not exist
``` 

Use the following configuration to avoid this issue:

```
dama_doctrine_test:
    enable_static_connection: false
```

See https://github.com/liip/LiipFunctionalTestBundle/issues/423 and https://github.com/dmaicher/doctrine-test-bundle/issues/58 for reference
