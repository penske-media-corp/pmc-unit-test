<?php

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Bootstrap;
use PMC\Unit_Test\Autoloader;

if ( ! defined( 'IS_PMC' ) ) {
	define( 'IS_PMC', true );
}

require_once __DIR__ . '/../autoload.php';

Autoloader::register( __NAMESPACE__, __DIR__ );
$bootstrap = Bootstrap::get_instance();


$bootstrap->start();
// EOF
