Installation
============

 1. Download the Bundle

    Open a command console, enter your project directory and execute the
    following command to download the latest stable version of this bundle:

    Use the next major version and help us find potential issues:
    ```bash
    $ composer require --dev liip/test-fixtures-bundle:^2.0.0-alpha1
    ```
    
    Or use the current stable version: see the [documentation for 1.x](https://github.com/liip/LiipTestFixturesBundle/blob/1.x/README.md).

    This command requires you to have Composer installed globally, as explained
    in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
    of the Composer documentation.

 2. Enable the Bundle

    Add the following line in the `app/AppKernel.php` file to enable this bundle only
    for the `test` environment:

    ```php
    <?php
    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            // ...
            if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
                // ...
                if ('test' === $this->getEnvironment()) {
                    $bundles[] = new Liip\TestFixturesBundle\LiipTestFixturesBundle();
                }
            }

            return $bundles;
        }

        // ...
    }
    ```
