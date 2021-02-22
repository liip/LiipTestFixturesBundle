# Changelog

See https://github.com/liip/LiipTestFixturesBundle/releases

## 2.x.0 (TBA)

- Removed callback functions passed from test classes to the fixtures service, they have been replaced by events, see these examples:
    - [declare subscriber(s)](../tests/AppConfigEvents/EventListener/FixturesSubscriber.php)
    - [service declaration to put in your test configuration](../tests/AppConfigEvents/config.yml)

## 1.1.0 (TBA)

- Added parameter `liip_test_fixtures.keep_database_and_schema` to avoid issue with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle)

## 1.0.0 (2019-06-11)

Initial release, code extracted from [LiipFunctionalTestBundle](https://github.com/liip/LiipFunctionalTestBundle)
