<?php
/**
 * Test mock mail for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;

/**
 * Class Test_Mock_Mail.
 */
class Test_Mock_Mail extends Base {

	public function test_mock() {
		$mocker = new \PMC\Unit_Test\Mocks\Mail();
		$this->assertEquals( 'mail', $mocker->provide_service() );

		$this->mock->mail( [
			'send' => true,
		] );

		$this->assertTrue( wp_mail( 'root@localhost', 'subject', 'message' ) );

		$this->mock->mail( [
			'send' => false,
		] );

		$this->assertFalse( wp_mail( 'root@localhost', 'subject', 'message' ) );
	}

}
