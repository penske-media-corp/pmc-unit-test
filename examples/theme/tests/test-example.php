<?php
namespace Examples\Theme\Tests;

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
