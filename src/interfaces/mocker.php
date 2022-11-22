<?php
/**
 * All data mocker class must implement this Interface to be auto registered by PMC Unit Test during plugin load.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Interfaces;

/**
 * Interface Mocker.
 */
interface Mocker {
	public function provide_service();
}
