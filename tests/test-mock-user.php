<?php
namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;

class Test_Mock_User extends Base {

	public function test_mock() {
		$mocker = new \PMC\Unit_Test\Mocks\User();
		$this->assertEquals( 'user', $mocker->provide_service() );

		$user = $this->mock->user( true )->get();
		$this->assertFalse( is_admin() );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertSame( wp_get_current_user(), $user );

		$user = $this->mock->user( true, 'admin' )->get();
		$this->assertTrue( is_admin() );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertSame( wp_get_current_user(), $user );

		$user = $this->mock->user( 'admin' )->get();
		$this->assertTrue( is_admin() );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertSame( wp_get_current_user(), $user );
		$this->assertContains( 'administrator', $user->roles );

		$user = $this->mock->user( 'admin', 'custom-screen' )->get();
		$this->assertTrue( is_admin() );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertSame( wp_get_current_user(), $user );
	}

}
