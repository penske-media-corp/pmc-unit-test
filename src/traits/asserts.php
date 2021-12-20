<?php
// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r

namespace PMC\Unit_Test\Traits;


/**
 * Define all custom asserts traits for base class
 *
 * Naming syntax:
 *
 * PMC Unit Test Framework extension methods
 *  - Shall use protected snake_case naming convention to avoid conflict naming with the official Unit Test Framework naming
 *  - The naming will indicate they are from PMC Unit Test Framework and not to confuse with camelCase from Unit Test Framework
 *  - eg. protected function assert_something( xyz );
 *
 */
trait Asserts {

	/**
	 * @param array $expected The expected array to validate against
	 * @param array $actual   The actual array result to validate
	 * @param string $message The message to display if validation failed
	 */
	public function assert_array_contains( array $expected, array $actual, $message = '' ) {
		foreach ( $expected as $key => $value ) {
			if ( ! isset( $actual[ $key ] ) ) {
				$this->fail( sprintf( "%s\nExpecting key %s in array not found\nExpecting: %s\nActual: %s\n", $message, $key, print_r( $expected, true ), print_r( $actual, true ) ) );
			} elseif ( is_array( $value ) ) {
				if ( is_array( $actual[ $key ] ) ) {
					$this->assert_array_contains( $value, $actual[ $key ] );
				} else {
					$this->fail( sprintf( "%s\nExpecting array [%s] value in array\nExpecting: %s\nActual: %s\n", $message, $key, print_r( $expected, true ), print_r( $actual, true ) ) );
				}
			} elseif ( $value !== $actual[ $key ] ) {
				$this->fail( sprintf( "%s\nExpecting array [%s]=%s value in array\nExpecting: %s\nActual: %s\n", $message, $key, $value, print_r( $expected, true ), print_r( $actual, true ) ) );
			}
		}
		$this->assertTrue( true );
	}

}
