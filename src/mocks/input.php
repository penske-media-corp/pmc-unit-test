<?php
namespace PMC\Unit_Test\Mocks;

use PMC\Unit_Test\Interfaces\Mocker as MockerInterface;
use PMC\Unit_Test\Interfaces\Seeder as SeederInterface;
use PMC\Unit_Test\Traits\Mocker as MockerTrait;

/**
 * Mocker for mocking the input global variable $_GET, $_POST, $_REQUEST, etc...
 *
 * Class Input
 * @package PMC\Unit_Test\Mocks
 */
final class Input
	implements MockerInterface {

	use MockerTrait;

	public function provide_service() : string {
		return 'input';
	}

	/**
	 * @param array $args
	 *    [
	 *         'POST|GET|REQUEST' => [ 'key' => 'value' ]
	 *    ]
	 * @return $this
	 */
	public function mock( $args = [] ) : self {
		foreach ( $args as $key => $values ) {
			$key = strtoupper( $key );
			if ( in_array( $key, [ 'GET', 'POST', 'REQUEST' ], true ) ) {
				$_SERVER['REQUEST_METHOD']                   = strtoupper( $key ); // phpcs:ignore
				$GLOBALS[ '_' . $_SERVER['REQUEST_METHOD'] ] = $values;  // phpcs:ignore
			}
		}
		return $this;
	}

}
