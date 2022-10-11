<?php
/**
 * Deprecated tests
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Deprecated;
use PMC\Unit_Test\Utility;
/**
 * Define our test class
 */
class Test_Deprecated extends Base {

	protected $_saved_env = [];
	protected $_log_file  = '/tmp/test-deprecated.log';

	/**
	 * Set up test variables
	 * tests/test-deprecated.php
	 *
	 * @return void
	 */
	public function setUp():void {
		parent::setUp();
		$this->_saved_env = [
			'log'  => getenv( 'PMC_PHPUNIT_DEPRECATED_LOG' ),
			'diff' => getenv( 'PMC_COMMIT_DIFF_FILE' ),
			'home' => getenv( 'HOME' ),
		];
	}
	function tearDown() {
		putenv( 'PMC_PHPUNIT_DEPRECATED_LOG=' . $this->_saved_env['log'] );
		putenv( 'PMC_COMMIT_DIFF_FILE=' . $this->_saved_env['diff'] );
		putenv( 'HOME=' . $this->_saved_env['home'] );
		parent::tearDown();
	}

	public function test_warn() {
		try {
			$instance           = Deprecated::get_instance();
			$instance->log_file = $this->_log_file;
			$instance->diff_info['tests/test-deprecated.php'][] = __LINE__ + 1;
			$instance->warn( 'test_warn', 'new_syntax' );
		} catch ( \Exception $ex ) {
			$exception = $ex;
		}
		$this->assertNotEmpty( $exception, 'Expect warn to trigger an error' );
		$message = $exception->getMessage();
		$this->assertContains( 'ERROR: Deprecated function call "test_warn"', $message );
		$this->assertContains( 'Please use new syntax: "new_syntax"', $message );
		$this->assertNotEmpty( $instance->stacks );

	}

	public function test_construct_shutdown() {
		Utility::unset_singleton( Deprecated::class );
		$test_file = '/tmp/test-deprecated.log';
		if ( file_exists( $test_file ) ) {
			unlink( $test_file );
		}
		putenv( 'HOME=/' );
		putenv( 'PMC_PHPUNIT_DEPRECATED_LOG=' . $test_file );
		putenv( 'PMC_COMMIT_DIFF_FILE=' );
		Deprecated::get_instance()->shutdown();
		$this->assertTrue( file_exists( $test_file ) );
		unlink( $test_file );

		Utility::unset_singleton( Deprecated::class );
		putenv( 'PMC_PHPUNIT_DEPRECATED_LOG=' );
		Deprecated::get_instance()->shutdown();
		$this->assertFalse( file_exists( $test_file ) );

	}

	public function test_load_diff_file() {
		$info = Deprecated::get_instance()->load_diff_file( __DIR__ . '/data/commit.diff' );
		$this->assertNotEmpty( $info );
		$this->assert_array_contains(
			[
				'pmc-unit-test-example/tests/test-my-plugin.php' => [ 4, 16, 21, 32, 35, 51 ],
				'pmc-unit-test/bootstrap.php' => [ 1 ],
			],
			$info 
		);
	}

}
