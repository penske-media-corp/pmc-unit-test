<?php
namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Diff_Parser;

class Test_Diff_Parse extends Base {
	public function test_parse() {
		$expecting = [
			'pmc-unit-test-example/tests/test-my-plugin.php' => [
				4,16,21,32,35,51
			],
			'pmc-unit-test/bootstrap.php' => [
				1,
			],
			'pmc-unit-test/fake.js' => []
		];
		$info = Diff_Parser::get_instance()->parse( __DIR__ . '/data/commit.diff' );

		$this->assertEquals( $expecting, $info );

		$this->assertEmpty( Diff_Parser::get_instance()->parse( 'not-found.diff' ) );
	}
}
