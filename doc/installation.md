# Installation

## 1. Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```shell
composer require --dev liip/test-fixtures-bundle:^3.0.0
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## 2. Enable the Bundle only in the test environment

Update the relevant line in the `config/bundles.php` file to enable this bundle only
for the `test` environment:

```diff
 return [
-    Liip\TestFixturesBundle\LiipTestFixturesBundle::class => ['dev' => true, 'test' => true],
+    Liip\TestFixturesBundle\LiipTestFixturesBundle::class => ['test' => true],
 ];
```

[Configuration](./configuration.md) »
