<?php
namespace PMC\Unit_Test\Tests\Traits;

use PMC\Unit_Test\Base;
use PMC\Unit_Test\Traits\Singleton;
use PMC\Unit_Test\Utility;

class Test_Singleton extends Base {
	public function test_get_instance() {
		$instance = _Using_Singleton::get_instance();
		$this->assertSame( $instance, _Using_Singleton::get_instance(), 'Singleton should return same object' );
		$this->assertEquals( $instance, _Using_Singleton::get_instance(), 'Singleton should return similar structured object');
		$this->assertTrue( $instance->init_called, 'Singleton should trigger _init method call' );

		Utility::unset_singleton( _Using_Singleton::class );
		$this->assertNotSame( $instance, _Using_Singleton::get_instance(), 'Singleton destroyed should not return same object' );
		$this->assertEquals( $instance, _Using_Singleton::get_instance(), 'Singleton should return similar structured object');
	}
}

class _Using_Singleton {
	use Singleton;

	public $init_called = false;

	protected function _init() {
		$this->init_called = true;
	}
}
