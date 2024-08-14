<?php
/**
 * Dummy singleton for unit test.
 *
 * @package pmc-unit-test
 */

declare( strict_types = 1 );

namespace PMC\Unit_Test\Tests\Mocks;

use PMC\Global_Functions\Traits\Singleton;

/**
 * Class Dummy_Singleton.
 */
final class Dummy_Singleton {
	use Singleton;

	/**
	 * Const used for testing.
	 *
	 * @var string
	 */
	private const PRIVATE_CONST = 'unit test constant';

	/**
	 * Static var used for testing.
	 *
	 * @var string
	 */
	private static string $_private_var = 'private_var';

	/**
	 * Class Constructor
	 */
	private function __construct() {
		$this->_setup_hooks();
	}

	/**
	 * Sets up the hooks for the class.
	 */
	private function _setup_hooks(): void {
		add_filter( 'pmc_test_filter', [ $this, 'test_filter' ], 11 );
		add_action( 'pmc_test_action', [ $this, 'test_action' ], 9 );
		add_action( 'pmc_global_filter', 'pmc_dummy_global_function' );
	}

	/**
	 * Removes the hooks set up by the _setup_hooks() method.
	 */
	private function _unset_hooks(): void {
		remove_filter( 'pmc_test_filter', [ $this, 'test_filter' ], 11 );
		remove_action( 'pmc_test_action', [ $this, 'test_action' ], 9 );
		remove_action( 'pmc_global_filter', 'pmc_dummy_global_function' );
	}

	/**
	 * Test filter callback function.
	 *
	 * This method is used as a callback for the 'pmc_test_filter' filter hook.
	 * It simply returns true, which can be useful for testing purposes.
	 *
	 * @return bool Always returns true.
	 */
	public function test_filter(): bool {
		return true;
	}

	/**
	 * Test action callback function.
	 *
	 * This method is used as a callback for the 'pmc_test_action' action hook.
	 * It simply echoes the string 'test action', which can be useful for testing purposes.
	 */
	public function test_action(): void {
		echo 'test action';
	}

	/**
	 * Static method.
	 *
	 * @return string The name of the static method.
	 */
	private static function _static_method(): string {
		return __FUNCTION__;
	}

	/**
	 * Hidden method.
	 *
	 * This is a private method that returns the name of the function as a string.
	 *
	 * @return string The name of the function.
	 */
	private function _hidden_method(): string {
		return __FUNCTION__;
	}

	/**
	 * Hidden method.
	 *
	 * This is a private method that echoes the string '_another_hidden_method'.
	 */
	private function _another_hidden_method(): void {
		echo '_another_hidden_method';
	}

	/**
	 * Throws an Exception with the name of the function as the message.
	 *
	 * @return never
	 * @throws \Exception Error with the name of the function as the message.
	 */
	private static function _static_throw_Exception(): never {
		throw new \Exception( __FUNCTION__ );
	}

	/**
	 * Throws an Error with the name of the function as the message.
	 *
	 * @return never
	 * @throws \Error Error with the name of the function as the message.
	 */
	private static function _static_throw_Error(): never {
		throw new \Error( __FUNCTION__ );
	}

	/**
	 * Throws an Exception with the name of the function as the message.
	 *
	 * @return never
	 * @throws \Exception Exception with the name of the function as the message.
	 */
	private function _throw_Exception(): never {
		throw new \Exception( __FUNCTION__ );
	}

	/**
	 * Throws an Error with the name of the function as the message.
	 *
	 * @return never
	 * @throws \Error Error with the name of the function as the message.
	 */
	private function _throw_Error(): never {
		throw new \Error( __FUNCTION__ );
	}
}

/**
 * Dummy global function that returns true.
 *
 * @return bool
 */
function pmc_dummy_global_function() {
	return true;
}
