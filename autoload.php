<?php
namespace PMC\Unit_Test;

require_once __DIR__ . '/src/classes/autoloader.php';
require_once __DIR__ . '/src/pluggable/wp-cli.php';

/**
 * Register autoloader
 */

Autoloader::register( 'PMC\Unit_Test', __DIR__ . '/src/classes' );
Autoloader::register( 'PMC\Unit_Test', __DIR__ . '/src' );
