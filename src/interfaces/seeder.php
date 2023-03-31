<?php
/**
 * All test data that support auto sample data generation must implement this interface.
 *
 * @package pmc-unit-test
 */
namespace PMC\Unit_Test\Interfaces;

/**
 * Interface Seeder.
 */
interface Seeder {
	public function seed();
	public function get_seeds();
}
