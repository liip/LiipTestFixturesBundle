# Configuration

Here is the full configuration with default values:

```yaml
# config/packages/test/liip_fixtures.yaml
liip_test_fixtures:
    keep_database_and_schema: false
    cache_metadata: true
```

- `keep_database_and_schema`: pass it to `true` to avoid deleting and creating the database and schema before each test, you'll have to create the database schema before running your tests:
  1. create database with `bin/console --env=test doctrine:database:create`:
  2. create schema with `bin/console --env=test doctrine:schema:update --force` or `bin/console --env=test doctrine:migrations:migrate --no-interaction`
- `cache_metadata`: using the cache slightly improve the performance

« [Installation](./installation.md) • [Database](./database.md) »
