<?php
namespace PMC\Unit_Test\Tests\Traits;

use PMC\Unit_Test\Base;

/**
 * @coversDefaultClass PMC\Unit_Test\Traits\Asserts
 *
 */
class Test_Asserts extends Base {
	/**
	 * @cover ::assert_array_contains
	 */
	public function test_assert_array_contains() {
		$expected = [
			'a' => [ 'b' => 'c' ],
			1 => [ 2 => 3 ],
		];
		$actual = [
			1 => [ 2 => 3 ],
			'a' => [ 'b' => 'c' ],
			'x' => [ 'y' => 'z' ],
		];
		$this->assert_array_contains( $expected, $actual );

		$exception = null;
		try {
			$this->assert_array_contains( [ 'd' => [ 'd' => 'e' ] ], $actual );
		}
		catch( \PHPUnit\Framework\AssertionFailedError $ex ) {
			$exception = $ex;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Expecting key d in array not found', $exception->getMessage() );

		$exception = null;
		try {
			$this->assert_array_contains( [ 'a' => [ 'b' => 'e' ] ], $actual );
		}
		catch( \PHPUnit\Framework\AssertionFailedError $ex ) {
			$exception = $ex;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Expecting array [b]=e value in array', $exception->getMessage() );

		$exception = null;
		try {
			$this->assert_array_contains( [ 'a' => [ 'b' => 'e' ] ], [ 'a' => 'b' ] );
		}
		catch( \PHPUnit\Framework\AssertionFailedError $ex ) {
			$exception = $ex;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Expecting array [a] value in array', $exception->getMessage() );

	}
}
