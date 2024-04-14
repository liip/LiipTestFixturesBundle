# Upgrade guide from 2.x to 3.x

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 2.x to the 3.x version:

### Upgrade the bundle

```shell
composer require --dev liip/test-fixtures-bundle:^3.0.0-alpha3
```

> [!TIP]
> The version will be `^3.0.0` once a stable release for the `3.x` branch will have been published.

### Remove `liip_test_fixtures.cache_db`

```diff
# app/config/config_test.yml
-liip_test_fixtures:
-    cache_db:
-        mysql: 'Liip\TestFixturesBundle\Services\DatabaseBackup\MysqlDatabaseBackup'
-        mongodb: '…'
+liip_test_fixtures: ~
```

### Remove subscriptions to these events

  - `LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE`
  - `LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE`
  - `LiipTestFixturesEvents::PRE_REFERENCE_SAVE`
  - `LiipTestFixturesEvents::POST_REFERENCE_SAVE`
