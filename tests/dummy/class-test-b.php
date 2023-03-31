<?php
/**
 * class Test_B for testing unit test class autoloader
 *
 * @author Amit Gupta <agupta@pmc.com>
 * @since  2019-04-02
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests\Dummy;

/**
 * Class Test_B.
 *
 * @codeCoverageIgnore
 */
class Test_B {
	protected $_protected_data = false;
	public $my_data            = false;
	public $field              = false;

	public function __construct() {
		$this->_protected_data = 'protected data';
	}

	public function get_protected_data() {
		return $this->_protected_data;
	}

	public function set_protected_data( $value ) {
		$this->_protected_data = $value;
	}
}    //end class

//EOF
