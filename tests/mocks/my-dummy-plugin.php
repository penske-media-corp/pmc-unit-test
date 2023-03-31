<?php
/**
 * My dummy plugin for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests\Mocks;

use PMC\Global_Functions\Traits\Singleton;

/**
 * Class My_Dummy_Plugin.
 */
class My_Dummy_Plugin {
	use Singleton;
	protected function __construct() {
	}
}

