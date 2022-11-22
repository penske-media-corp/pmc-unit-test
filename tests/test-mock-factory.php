<?php
/**
 * Test mock factory for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Mock\Factory;
use PMC\Unit_Test\Mocks\Post;
use PMC\Unit_Test\Utility;

/**
 * Class Test_Mock_Factory.
 */
class Test_Mock_Factory extends Base {
	public function test_factory() {
		Utility::set_and_get_hidden_property( $this->mock, '_test_object', false );
		$this->mock()->set_test_object( $this );
		$this->assertEquals( $this, Utility::get_hidden_property( $this->mock, '_test_object' ) );

		$this->mock->register( function( $value ) { return $value; }, 'test' );
		$this->assertEquals( 'unit-test', $this->mock->test( 'unit-test' ) );

		Utility::assert_error( \Error::class, function() {
			$this->mock->register( function( $value ) {} );
		} );

		Utility::assert_error( \Error::class, function() {
			$this->mock->register( (object)[] );
		} );

		Utility::assert_error( \Error::class, function() {
			$this->mock->register( '_string_failed_' );
		} );

		Utility::assert_error( \Error::class, function() {
			$this->mock->_failed();
		} );

		$this->mock()->reset();
	}

	public function test_register() {
		$factory = \PMC\Unit_Test\Mocks\Factory::get_instance();
		$mocks = Utility::get_hidden_property( $factory, '_registered_mocks' );
		unset( $mocks['post'] );
		Utility::set_and_get_hidden_property( $factory, '_registered_mocks', $mocks );
		$factory->register( Post::class );
		$mocks = Utility::get_hidden_property( $factory, '_registered_mocks' );
		$this->assertNotEmpty( $mocks['post'] );
	}
}
