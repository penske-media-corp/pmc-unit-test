<?php
/**
 * Test Unit Test Utilities
 *
 * @package pmc-unit-test
 *
 * @since 2018-10-19 Mike Auteri
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;
use PMC\Unit_Test\Tests\Mocks\Dummy_Singleton;

/**
 * Test Utility
 *
 * @group pmc-unit-test
 * @group pmc-unit-test-utility
 *
 * @coversDefaultClass \PMC\Unit_Test\Utility
 */
class Test_Utility extends Base {

	var $filter_name = 'test_mock_url';
	var $test_url    = 'https://localhost/api/';

	/**
	 * Set up test variables
	 *
	 * @return void
	 */
	public function setUp():void {

		// To speed up unit test, we bypass files scanning on upload folder.
		self::$ignore_files = true;
		parent::setUp();

		add_filter( 'http_request_host_is_external', '__return_true' );

		/**
		 * Mock up data. This filter can exist in your setUp or bootstrap.php.
		 * We're only including in setUp here to test it.
		 */
		add_filter(
			'pmc_unit_test__http_mocks',
			function ( $mocks ) {
				$mock_url = $this->test_url;

				return array_merge(
					$mocks,
					[
						'mock-test' => [
							[
								'request'  => [
									'url' => 'http://localhost/?mock-test1',
								],
								'response' => [
									'body'    => 'mock-test',
									'headers' => [ 'Header' => 'value' ],
									'status'  => [
										'code'    => 200,
										'message' => 'OK',
									],
								],
							],
							[
								'request'  => [
									'url' => 'http://localhost/?mock-test2',
								],
								'response' => [
									'file' => __DIR__ . '/mocks/mock-test/default.json',
								],
							],
							[
								'request'  => [
									'url' => 'http://localhost/?mock-test3',
								],
								'response' => [],
							],

							[
								'request'  => [
									'url' => $mock_url . '?test=test_filter_pre_http_request',
								],
								'response' => [
									'body' => file_get_contents( __DIR__ . '/mocks/mock-test/default.json' ), // phpcs:ignore
								],
							],
							[
								'request'  => [
									'url' => $mock_url . '?test=test_filter_pre_http_request_404',
								],
								'response' => [
									'body'   => null,
									'status' => [
										'code'    => 404,
										'message' => 'Not found',
									],
								],
							],
							[
								'request'  => [
									'url' => $mock_url . '?test=test_filter_pre_http_request_post',
								],
								'response' => [
									'file' => __DIR__ . '/mocks/mock-test/default.json', // phpcs:ignore
								],
							],
							[
								'request'  => [
									'url' => $mock_url . '?test=test_filter_pre_http_request_malformed_mocks',
								],
								'response' => [
									'body' => file_get_contents( __DIR__ . '/mocks/mock-test/default.json' ), // phpcs:ignore
								],
							],
							[
								'request'  => [
									'url' => $mock_url . '?test=test_filter_pre_http_request_malformed_mock_group',
								],
								'response' => [
									'body' => file_get_contents( __DIR__ . '/mocks/mock-test/default.json' ), // phpcs:ignore
								],
							],
							[
								'request'  => [
									'url' => $mock_url . '?test=test_filter_pre_http_request_malformed_mock_group_item',
								],
								'response' => [
									'file' => __DIR__ . '/mocks/mock-test/default.json',
								],
							],
						],
					] 
				);
			} 
		);

		/**
		 * Test parameter for &test=method will automatically setup the mock
		 * when executing the corresponding test of the same name.
		 */
		$per_test_url = $this->test_url . '?test=' . $this->getName();

		add_filter(
			$this->filter_name,
			function( $url ) use ( $per_test_url ) { // phpcs:ignore;
				return $per_test_url;
			} 
		);
	}

	/**
	 * Remove filters after tests
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_all_filters( $this->filter_name );
	}

	/**
	 * Test filter_pre_http_request utility json
	 *
	 * @covers ::filter_pre_http_request()
	 */
	public function test_filter_pre_http_request() {
		$response = $this->_make_http_request();
		$body     = wp_remote_retrieve_body( $response );
		$feed     = json_decode( $body );

		$this->assertSame( 3, $feed->count );
		$this->assertSame( 'Test Title 1', $feed->data[0]->title );
	}

	/**
	 * Test filter_pre_http_request utility 404
	 *
	 * @covers ::filter_pre_http_request()
	 */
	public function test_filter_pre_http_request_404() {
		$response = $this->_make_http_request();
		$this->assertSame( 404, wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Test filter_pre_http_request utility error
	 *
	 * @covers ::filter_pre_http_request
	 */
	public function test_filter_pre_http_request_post() {
		$this->assertFalse( is_wp_error( $this->_make_http_request() ) );
	}

	/**
	 * Test filter_pre_http_request utility malformed mock
	 *
	 * @covers ::filter_pre_http_request
	 */
	public function test_filter_pre_http_request_malformed_mocks() {
		// Return mocks malformed as string.
		add_filter(
			'pmc_unit_test__http_mocks',
			function( $mocks ) { // phpcs:ignore;
				return 'foobar';
			} 
		);

		$this->assertTrue( is_wp_error( $this->_make_http_request() ) );
	}

	/**
	 * Test filter_pre_http_request utility group
	 *
	 * @covers ::filter_pre_http_request
	 */
	public function test_filter_pre_http_request_malformed_mock_group() {
		// Return mocks malformed as string.
		add_filter(
			'pmc_unit_test__http_mocks',
			function( $mocks ) { // phpcs:ignore;
				return [ 'mock-test' => 'not an array' ];
			} 
		);

		$this->assertTrue( is_wp_error( $this->_make_http_request() ) );
	}

	/**
	 * Test filter_pre_http_request utility
	 *
	 * @covers ::filter_pre_http_request
	 */
	public function test_filter_pre_http_request_malformed_mock_group_item() {
		// Return mocks malformed as string.
		add_filter(
			'pmc_unit_test__http_mocks',
			function( $mocks ) { // phpcs:ignore;
				return [ 'mock-test' => [ 'not an array' ] ];
			} 
		);

		$this->assertTrue( is_wp_error( $this->_make_http_request() ) );
	}

	/**
	 * Helper for making HTTP Requests.
	 *
	 * @return WP_Error|array
	 */
	private function _make_http_request() {
		return wp_safe_remote_get( apply_filters( $this->filter_name, $this->test_url ) );
	}

	/**
	 * Test get_instance utility
	 *
	 * @return void
	 */
	public function test_get_instance() {
		$this->assertEmpty( Utility::get_instance( Dummy_Singleton::class ) );
		$instance     = Dummy_Singleton::get_instance();
		$test_intance = Utility::get_instance( Dummy_Singleton::class );
		$this->assertEquals( $instance, $test_intance );
	}

	/**
	 * 100% code coverage for Utility class
	 */
	public function test_code_coverage() {
		$dummy_object = Dummy_Singleton::get_instance();

		$this->assertTrue( Utility::has_property( $dummy_object, '_private_var' ) );
		$this->assertTrue( Utility::has_property( Dummy_Singleton::class, '_private_var' ) );

		$this->assertEquals( 'static_method', Utility::invoke_hidden_static_method( Dummy_Singleton::class, 'static_method' ) );
		$this->assertEquals( 'static_method', Utility::invoke_hidden_method( $dummy_object, 'static_method' ) );
		$this->assertEquals( 'hidden_method', Utility::invoke_hidden_method( $dummy_object, 'hidden_method' ) );

		$this->assertEquals( 'private_var', Utility::get_hidden_static_property( Dummy_Singleton::class, '_private_var' ) );
		$this->assertEquals( 'private_var', Utility::get_hidden_property( $dummy_object, '_private_var' ) );

		$this->assertEquals( 'test1', Utility::set_and_get_hidden_static_property( Dummy_Singleton::class, '_private_var', 'test1' ) );
		$this->assertEquals( 'test2', Utility::set_and_get_hidden_property( $dummy_object, '_private_var', 'test2' ) );

		$this->assertEquals( 'test2', Utility::get_hidden_static_property( Dummy_Singleton::class, '_private_var' ) );
		$this->assertEquals( 'test2', Utility::get_hidden_property( $dummy_object, '_private_var' ) );

		$bufs = Utility::buffer_and_return(
			function() {
				echo 'buffer_and_return';
			}
		);
		$this->assertEquals( 'buffer_and_return', $bufs );

		add_action(
			'get_header',
			function() {
				echo '<--// header //-->';
			} 
		);
		add_action(
			'get_footer',
			function() {
				echo '<--// footer //-->';
			} 
		);

		$this->mock->wp( [] );
		$bufs = Utility::simulate_wp_script_render();
		$this->assertContains( '<--// header //-->', $bufs );
		$this->assertContains( '<--// footer //-->', $bufs );

		Utility::assert_exception(
			\ErrorException::class,
			function() {
				Utility::buffer_and_return( false );
			} 
		);

		Utility::assert_error(
			\Error::class,
			function() {
				throw new \Error();
			} 
		);

		Utility::assert_exception_on_method( \Exception::class, $dummy_object, 'throw_Exception' );
		Utility::assert_exception_on_method( \Exception::class, Dummy_Singleton::class, 'throw_Exception' );
		Utility::assert_exception_on_method( \Exception::class, $dummy_object, 'static_throw_Exception' );
		Utility::assert_exception_on_method( \Exception::class, Dummy_Singleton::class, 'static_throw_Exception' );

		Utility::assert_exception_on_hidden_method( \Exception::class, $dummy_object, 'throw_Exception' );
		Utility::assert_exception_on_hidden_static_method( \Exception::class, Dummy_Singleton::class, 'static_throw_Exception' );

		Utility::assert_error_on_method( \Error::class, $dummy_object, 'throw_Error' );
		Utility::assert_error_on_method( \Error::class, $dummy_object, 'static_throw_Error' );
		Utility::assert_error_on_method( \Error::class, Dummy_Singleton::class, 'static_throw_Error' );

		Utility::assert_error_on_hidden_method( \Error::class, $dummy_object, 'throw_Error' );
		Utility::assert_error_on_hidden_method( \Error::class, $dummy_object, 'static_throw_Error' );
		Utility::assert_error_on_hidden_static_method( \Error::class, Dummy_Singleton::class, 'static_throw_Error' );


		$this->assertNotEmpty( Utility::get_instance( Dummy_Singleton::class ) );
		Utility::unset_singleton( Dummy_Singleton::class );
		$this->assertEmpty( Utility::get_instance( Dummy_Singleton::class ) );

		$dummy_object = Dummy_Singleton::get_instance();
		$this->assertNotEmpty( Utility::get_instance( Dummy_Singleton::class ) );
		Utility::unset_singleton( $dummy_object );
		$this->assertEmpty( Utility::get_instance( Dummy_Singleton::class ) );

		$dummy_object = Dummy_Singleton::get_instance();
		$this->assertNotEmpty( Utility::get_instance( Dummy_Singleton::class ) );
		Utility::unset_singleton_instance( Dummy_Singleton::class, $dummy_object );
		$this->assertEmpty( Utility::get_instance( Dummy_Singleton::class ) );

		$dummy_object       = Dummy_Singleton::get_instance();
		$dummy_object->name = 'dummy_object';
		$instance           = Utility::unset_singleton( Dummy_Singleton::class );
		$this->assertEquals( $dummy_object, $instance );

		$new_instance = Dummy_Singleton::get_instance();
		$this->assertNotEquals( $dummy_object, $new_instance );

		Utility::restore_singleton( $dummy_object );
		$old_instance = Dummy_Singleton::get_instance();
		$this->assertEquals( $dummy_object, $old_instance );

	}

	/**
	 * Test buffer_and_return_hidden_method utility
	 *
	 * @covers ::buffer_and_return_hidden_method
	 */
	public function test_buffer_and_return_hidden_method() : void {

		$dummy_object = Dummy_Singleton::get_instance();

		$output_to_test = Utility::buffer_and_return_hidden_method( $dummy_object, '_another_hidden_method' );

		$this->assertContains( '_another_hidden_method', $output_to_test );

	}

	/**
	 * Test headers_list utility
	 *
	 * @return void
	 */
	public function test_headers_list() {
		$headers = Utility::headers_list(
			function() {
				header( 'Header: Test' );
			} 
		);

		$this->assertEquals( [ 'Header: Test' ], $headers );
	}

}
