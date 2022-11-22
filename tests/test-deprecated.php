<?php
/**
 * Test deprecated for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Deprecated;
use PMC\Unit_Test\Utility;

/**
 * Class Test_Deprecated.
 */
class Test_Deprecated extends Base {

	/**
	 * Environment variables
	 *
	 * @var array $_saved_env Environment variables to be restored.
	 */
	protected $_saved_env = [];

	/**
	 * Log File
	 *
	 * @var string $_log_file Tmp log file.
	 */
	protected $_log_file = '/tmp/test-deprecated.log';

	/**
	 * Set up test variables
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->_saved_env = [
			'log'  => getenv( 'PMC_PHPUNIT_DEPRECATED_LOG' ),
			'diff' => getenv( 'PMC_COMMIT_DIFF_FILE' ),
			'home' => getenv( 'HOME' ),
		];
	}

	/**
	 * Reset test environment between tests
	 *
	 * Ignoring putenv for testing purposes.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		putenv( 'PMC_PHPUNIT_DEPRECATED_LOG=' . $this->_saved_env['log'] ); // phpcs:ignore.
		putenv( 'PMC_COMMIT_DIFF_FILE=' . $this->_saved_env['diff'] ); // phpcs:ignore.
		putenv( 'HOME=' . $this->_saved_env['home'] ); // phpcs:ignore.
		parent::tearDown();
	}

	/**
	 * Test warnings
	 *
	 * @return void
	 */
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
		$this->assertStringContainsString( 'ERROR: Deprecated function call "test_warn"', $message );
		$this->assertStringContainsString( 'Please use new syntax: "new_syntax"', $message );
		$this->assertNotEmpty( $instance->stacks );

	}

	/**
	 * Testing shutdown
	 *
	 * Ignoring putenv for testing purposes.
	 *
	 * @return void
	 */
	public function test_construct_shutdown() {
		Utility::unset_singleton( Deprecated::class );
		$test_file = '/tmp/test-deprecated.log';
		if ( file_exists( $test_file ) ) {
			unlink( $test_file );  // phpcs:ignore.
		}
		putenv( 'HOME=/' ); // phpcs:ignore.
		putenv( 'PMC_PHPUNIT_DEPRECATED_LOG=' . $test_file ); // phpcs:ignore.
		putenv( 'PMC_COMMIT_DIFF_FILE=' ); // phpcs:ignore.
		Deprecated::get_instance()->shutdown();
		$this->assertTrue( file_exists( $test_file ) );
		unlink( $test_file );  // phpcs:ignore.

		Utility::unset_singleton( Deprecated::class );
		putenv( 'PMC_PHPUNIT_DEPRECATED_LOG=' ); // phpcs:ignore.
		Deprecated::get_instance()->shutdown();
		$this->assertFalse( file_exists( $test_file ) );

	}

	/**
	 * Test file diff loader
	 *
	 * @return void
	 */
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
