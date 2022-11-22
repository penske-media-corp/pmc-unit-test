<?php
/**
 * Test mock input for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;

/**
 * Class Test_Mock_Input.
 */
class Test_Mock_Input extends Base {

	public function test_mock() {
		$mocker = new \PMC\Unit_Test\Mocks\Input();
		$this->assertEquals( 'input', $mocker->provide_service() );

		$mocked_data = [ 'k1' => 'v1', 'k2' => 'v2' ];

		$this->mock->input( [
			'POST' => $mocked_data,
		] );
		$this->assertEquals( $mocked_data, $_POST );

		$this->mock->input( [
			'post' => [ 'k1' => 'v1', 'k2' => 'v2' ],
		] );
		$this->assertEquals( $mocked_data, $_POST );
		$this->assertEquals( 'POST', $_SERVER['REQUEST_METHOD'] );

		$this->mock->input( [
			'GET' => [ 'k1' => 'v1', 'k2' => 'v2' ],
		] );
		$this->assertEquals( $mocked_data, $_GET );
		$this->assertEquals( 'GET', $_SERVER['REQUEST_METHOD'] );

		$this->mock->input( [
			'REQUEST' => [ 'k1' => 'v1', 'k2' => 'v2' ],
		] );
		$this->assertEquals( $mocked_data, $_REQUEST );
		$this->assertEquals( 'REQUEST', $_SERVER['REQUEST_METHOD'] );

	}

}
