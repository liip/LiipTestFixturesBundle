# Upgrade guide from 2.x to 3.x

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 2.x to the 3.x version:

### Upgrade the bundle

```shell
composer require --dev liip/test-fixtures-bundle:^3.1.0
```

### Remove `liip_test_fixtures.cache_db`

> [!TIP]
> Only for `3.0`, this feature has been restored in `3.1`.

```diff
# app/config/config_test.yml
-liip_test_fixtures:
-    cache_db:
-        mysql: 'Liip\TestFixturesBundle\Services\DatabaseBackup\MysqlDatabaseBackup'
-        mongodb: '…'
+liip_test_fixtures: ~
```

### Remove subscriptions to these events

> [!TIP]
> Only for `3.0`, these events have been restored in `3.1`. 

  - `LiipTestFixturesEvents::PRE_FIXTURE_BACKUP_RESTORE`
  - `LiipTestFixturesEvents::POST_FIXTURE_BACKUP_RESTORE`
  - `LiipTestFixturesEvents::PRE_REFERENCE_SAVE`
  - `LiipTestFixturesEvents::POST_REFERENCE_SAVE`
