<?php
/**
 * Base ajax class for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;
use PMC\Unit_Test\Tests\Mocks\My_Dummy_Plugin;

/**
 * Class Test_Base_Ajax.
 */
class Test_Base_Ajax extends \PMC\Unit_Test\Base_Ajax {
	protected function _load_plugin() {
	}

	public function test_ajax() {
		add_action( 'wp_ajax_unittest', function() {
			wp_send_json_success( true );
		} );

		$result = $this->do_ajax( 'unittest' );
		$this->assertTrue( $result->data );

	}

}
