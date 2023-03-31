<?php
/**
 * Dummy singleton for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests\Mocks;

use PMC\Global_Functions\Traits\Singleton;

/**
 * Class Dummy_Singleton.
 */
class Dummy_Singleton {
	use Singleton;

	private static $_private_var = 'private_var';

	protected function __construct() {
		$this->_setup_hooks();
	}

	protected function _setup_hooks() {
		add_filter( 'pmc_test_filter', [ $this, 'test_filter' ], 11 );
		add_action( 'pmc_test_action', [ $this, 'test_action' ], 9 );
		add_action( 'pmc_global_filter', 'pmc_dummy_global_function' );
	}

	protected function _unset_hooks() : void {
		remove_filter( 'pmc_test_filter', [ $this, 'test_filter' ], 11 );
		remove_action( 'pmc_test_action', [ $this, 'test_action' ], 9 );
		remove_action( 'pmc_global_filter', 'pmc_dummy_global_function' );
	}

	public function test_filter() {
		return true;
	}

	public function test_action() {
		echo 'test action';
	}

	private static function static_method() {
		return __FUNCTION__;
	}

	private function hidden_method() {
		return __FUNCTION__;
	}

	private function _another_hidden_method() : void {
		echo '_another_hidden_method';
	}

	private static function static_throw_Exception() {
		throw new \Exception(__FUNCTION__);
	}

	private static function static_throw_Error() {
		throw new \Error(__FUNCTION__);
	}

	private function throw_Exception() {
		throw new \Exception(__FUNCTION__);
	}

	private function throw_Error() {
		throw new \Error(__FUNCTION__);
	}

}

function pmc_dummy_global_function() {
	return true;
}
