<?php
/**
 * Defined the standard test base class for all unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test;

/**
 * Class Base_Ajax.
 *
 * Define as abstract class to prevent test suite from scanning for test method
 *
 * Should only add method that is specific for Ajax testing only
 * For common shared code, please @see traits/base.php
 */
abstract class Base_Ajax extends \WP_Ajax_UnitTestCase {
	use \PMC\Unit_Test\Traits\Asserts;
	use \PMC\Unit_Test\Traits\Base;

	protected function do_ajax( $ajax_handle, $message = false, $validate = true ) {

		// IMPORTANT: we need to clear out the last response data before we proceed.
		// The unit test ajax handler append the data to this variable.
		$this->_last_response = '';
		try {
			$this->_handleAjax( $ajax_handle );
		} catch ( \WPAjaxDieContinueException $e ) { // phpcs:ignore
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		if ( $validate ) {
			$this->assertInternalType( 'object', $response, $message );
			$this->assertObjectHasAttribute( 'success', $response, $message );
		}

		return $response;

	}

} //end class

//EOF
