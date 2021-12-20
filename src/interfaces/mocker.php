<?php
namespace PMC\Unit_Test\Interfaces;

/**
 * All data mocker class must implement this Interface to be auto registered by PMC Unit Test during plugin load.
 *
 * Interface Mocker
 * @package PMC\Unit_Test\Interfaces
 */
interface Mocker {
	public function provide_service();
}
