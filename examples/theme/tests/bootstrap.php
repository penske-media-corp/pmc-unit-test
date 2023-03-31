<?php
/**
 * Bootstrap for unit test.
 *
 * @package pmc-unit-test
 */

namespace Examples\Theme\Tests;

use PMC\Unit_Test\Bootstrap;
use PMC\Unit_Test\Autoloader;

require_once __DIR__ . '/../vendor/autoload.php';

Autoloader::register( __NAMESPACE__, __DIR__ );
$bootstrap = Bootstrap::get_instance();

$bootstrap->start();
// EOF
