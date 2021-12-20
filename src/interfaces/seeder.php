<?php
namespace PMC\Unit_Test\Interfaces;


/**
 * All test data that support auto sample data generation must implement this interface
 *
 * Interface Seeder
 * @package PMC\Unit_Test\Interfaces
 */
interface Seeder {
	public function seed();
	public function get_seeds();
}
