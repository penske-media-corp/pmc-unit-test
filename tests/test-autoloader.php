<?php
namespace PMC\Unit_Test\Tests;

use \PMC\Unit_Test\Autoloader;

// All test extends the base test abstract class

/**
 * Class Test_Autoloader
 *
 * @group pmc-unit-test-autoloader
 *
 * @coversDefaultClass PMC\Unit_Test\Autoloader
 */
class Test_Autoloader extends Base {

	public function test_auto_loading() {
		Autoloader::register( 'AUTOLOAD', __DIR__ . '/mocks/mock-test' );
		$instance = new \AUTOLOAD\Auto_Load_Dummy_Class();
		$this->assertInstanceOf( \AUTOLOAD\Auto_Load_Dummy_Class::class, $instance );
		$instance = new \AUTOLOAD\Auto_Load_Sub_Folder\Auto_Load_Dummy_Class();
		$this->assertInstanceOf( \AUTOLOAD\Auto_Load_Sub_Folder\Auto_Load_Dummy_Class::class, $instance );

		$instance_b = new \AUTOLOAD\Auto_Load_Dummy_Class_B();
		$this->assertInstanceOf( \AUTOLOAD\Auto_Load_Dummy_Class_B::class, $instance_b );

		$this->expectException( \Exception::class );
		Autoloader::register( false , false );

		$this->expectException( \Exception::class );
		Autoloader::register( 'AUTOLOAD' , false );

	}

	public function test_load_resource() {

		/*
		 * Test loading class which does not have 'class' prefix in file name
		 */
		$test_a = new \PMC\Unit_Test\Tests\Dummy\Test_A();
		$this->assertInstanceOf( \PMC\Unit_Test\Tests\Dummy\Test_A::class, $test_a );

		/*
		 * Test loading class which has 'class' prefix in file name
		 */
		$test_b = new \PMC\Unit_Test\Tests\Dummy\Test_B();
		$this->assertInstanceOf( \PMC\Unit_Test\Tests\Dummy\Test_B::class, $test_b );

	}

}
