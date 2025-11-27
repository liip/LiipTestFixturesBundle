<?php /** @noinspection ALL */

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\ErrorHandler\ErrorHandler;

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}
/** @noinspection PhpIncludeInspection */
$autoload = require $file;

# https://github.com/symfony/symfony/issues/53812#issuecomment-1962740145
set_exception_handler([new ErrorHandler(), 'handleException']);
