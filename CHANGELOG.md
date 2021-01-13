# Changelog

## 2.0.0

- Removed `FixturesTrait`:
  - Access through the service `liip_test_fixtures.services.database_tool_collection` instead
  - Use `loadAliceFixture(…)` instead of `loadFixtureFiles(…)`

## 1.x.0 (TBA)

- Deprecated `FixturesTrait` #26

## 1.1.0

- Added parameter `liip_test_fixtures.keep_database_and_schema` to avoid issue with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle)

## 1.0.0 (2019-06-11)

Initial release, code extracted from [LiipFunctionalTestBundle](https://github.com/liip/LiipFunctionalTestBundle)
