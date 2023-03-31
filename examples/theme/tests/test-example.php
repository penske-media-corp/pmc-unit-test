<?php
/**
 * Example test for unit test.
 *
 * @package pmc-unit-test
 */

namespace Examples\Theme\Tests;

/**
 * Class Test_Example.
 */
class Test_Example extends Base {

	public function test_mock_post() {
		$post = $this->mock->post(
			[
				'post_title' => 'test',
			] 
		)->get();
		$this->assertEquals( 'test', $post->post_title );
	}

}
