<?php
// Defined the standard test base class for all unit test

namespace PMC\Unit_Test;

/**
 * Define as abstract class to prevent test suite from scanning for test method
 *
 *  Should only add method that is specific for non Ajax testing only
 * For common shared code, please @see traits/base.php
 *
 */
abstract class Base extends \WP_UnitTestCase {
	use \PMC\Unit_Test\Traits\Asserts;
	use \PMC\Unit_Test\Traits\Base;
} //end class

//EOF
