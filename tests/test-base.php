<?php
/**
 * Define our test class
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;
use PMC\Unit_Test\Tests\Mocks\My_Dummy_Plugin;
use PMC\Unit_Test\Tests\Mocks\Dummy_Singleton;
use PMC\Unit_Test\Tests\Dummy\Test_Construct;

/**
 * Class Test_Base
 *
 * @coversDefaultClass PMC\Unit_Test\Traits\Base
 *
 * @package PMC\Unit_Test\Tests
 */
class Test_Base extends Base {

	/**
	 * Instantiate variables needed for the tests.
	 *
	 * @return void
	 */
	public function setUp():void {
		$GLOBALS['wp_rewrite']->permalink_structure = false;
		parent::setUp();
	}

	/**
	 * Test permalink struct
	 *
	 * @return void
	 */
	public function test_permalink_structure() {
		$this->assertEquals( '/%year%/%monthnum%/%category%/%postname%-%post_id%/', $GLOBALS['wp_rewrite']->permalink_structure );
	}

	/**
	 * Test assert_setup_hooks
	 *
	 * @return void
	 */
	public function test_assert_setup_hooks() {

		$instance = Dummy_Singleton::get_instance();

		$hooks = [
			[
				'type'     => 'filter',
				'name'     => 'pmc_test_filter',
				'priority' => 11,
				'listener' => [ $instance, 'test_filter' ],
			],
			[
				'type'     => 'action',
				'name'     => 'pmc_test_action',
				'priority' => 9,
				'listener' => [ $instance, 'test_action' ],
			],
			[
				'type'     => 'action',
				'name'     => 'pmc_global_filter',
				'priority' => 10,
				'listener' => 'pmc_dummy_global_function',
			],
		];

		$this->assert_setup_hooks( $hooks, $instance );

		Utility::unset_singleton( $instance );

	}

	/**
	 * Test assert_unset_hooks
	 *
	 * @return void
	 */
	public function test_assert_unset_hooks() {

		$instance = Dummy_Singleton::get_instance();

		$hooks = [
			[
				'type'     => 'filter',
				'name'     => 'pmc_test_filter',
				'priority' => 11,
				'listener' => [ $instance, 'test_filter' ],
			],
			[
				'type'     => 'action',
				'name'     => 'pmc_test_action',
				'priority' => 9,
				'listener' => [ $instance, 'test_action' ],
			],
			[
				'type'     => 'action',
				'name'     => 'pmc_global_filter',
				'priority' => 10,
				'listener' => 'pmc_dummy_global_function',
			],
		];

		$this->assert_unset_hooks( $hooks, $instance );

		Utility::unset_singleton( $instance );

	}

	/**
	 * Test base class
	 *
	 * Ignoring the false positive on the PHP4-style constructor.
	 *
	 * @return void
	 */
	public function test_base() { // phpcs:ignore.
		My_Dummy_Plugin::get_instance();
		$this->assert_plugin_loaded( My_Dummy_Plugin::class );
		$this->remove_added_uploads();

		$this->assertContains(
			'Closure()',
			$this->_sprint_callback(
				function () {
				}
			)
		);
		$this->assertContains( 'Array', $this->_sprint_callback( [] ) );

	}

	/**
	 * Test assertRedirect
	 *
	 * @return void
	 */
	public function test_assertRedirect() {

		// Note: We want to test the trait matching the redirect status by override the default wp_safe_redirect status  to 301
		// we really don't want to do the test using the default 302 because we want to valid our unit trait test function
		// if it work for override, it should work for default as well.

		$this->assert_redirect_to(
			'/test',
			function () {
				// Simulate code to trigger redirect with code 301.
				wp_safe_redirect( '/test', 301 );  // phpcs:ignore.
			},
			301
		);

		$this->assert_not_redirect(
			function () {
				throw new \Exception( 'test' );
			}
		);

		$exception = null;
		try {
			// Ignored because testing.
			$this->assert_not_redirect(
				function () {
					wp_safe_redirect( '/test', 301 );  // phpcs:ignore.
				}
			);
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Expecting no call to wp_redirect', $exception->getMessage() );
	}

	/**
	 * Testing snapshot functions
	 *
	 * @return void
	 */
	public function test_snapshot() {
		$this->_default_vars = [ 'my_data', '_protected_data' ];

		$obj = new \PMC\Unit_Test\Tests\Dummy\Test_A();
		$this->_take_snapshot( $obj, false );

		$obj->my_data = 'version1';
		$obj->field1  = 'version1';
		$obj->set_protected_data( 'version1' );
		$this->_take_snapshot( $obj, 'version1' );

		$obj->my_data = 'version2';
		$obj->field1  = 'version2';
		$obj->set_protected_data( 'version2' );
		$this->_take_snapshot( $obj, 'version2' );

		$obj->field1 = 'should not change';
		$this->_restore_snapshot( $obj, false );
		$this->assertEquals( false, $obj->my_data );
		$this->assertEquals( 'should not change', $obj->field1 );
		$this->assertEquals( 'protected data', $obj->get_protected_data() );

		$this->_restore_snapshot( $obj, 'version1' );
		$this->assertEquals( 'version1', $obj->my_data );
		$this->assertEquals( 'should not change', $obj->field1 );
		$this->assertEquals( 'version1', $obj->get_protected_data() );

		$this->_restore_snapshot( $obj, 'version2' );
		$this->assertEquals( 'version2', $obj->my_data );
		$this->assertEquals( 'should not change', $obj->field1 );
		$this->assertEquals( 'version2', $obj->get_protected_data() );

		$obj = new \PMC\Unit_Test\Tests\Dummy\Test_B();
		$this->_restore_snapshot( $obj, 'version2' );
		$this->assertEquals( false, $obj->my_data );
		$this->assertEquals( false, $obj->field );
		$this->assertEquals( 'protected data', $obj->get_protected_data() );

	}

	/**
	 * Testing go_to
	 *
	 * @return void
	 */
	public function test_go_to() {
		$this->go_to( home_url( '/' ) );
		$this->assertTrue( is_home() );

		$post = $this->mock->post()->get();
		$this->assertEquals( $post, get_post() );

		$this->go_to( $post );
		$this->assertEquals( $post, get_post() );

		$this->go_to( get_permalink() );
		$this->assertEquals( $post, get_post() );

	}

	/**
	 * Testing traits
	 *
	 * @return void
	 */
	public function test_traits() {
		$saved = $GLOBALS['shortcode_tags'];

		$object = (object) [ 'value' => 'test' ];
		// Ignored for testing.
		$GLOBALS['shortcode_tags'] = [ // phpcs:ignore.
			'test' => $object,
		];

		$this->_backup_shortcodes();
		$object->value = 'modified';

		$this->assertNotEmpty( $this->_saved_shortcodes );
		$this->assertNotEmpty( $this->_saved_shortcodes['test'] );
		$this->assertNotEquals( $object, $this->_saved_shortcodes['test'] );
		// Ignored for testing.
		$this->assertEquals( $object, $GLOBALS['shortcode_tags']['test'] ); // phpcs:ignore.
		$this->assertEquals( 'modified', $GLOBALS['shortcode_tags']['test']->value ); // phpcs:ignore.

		// Ignored for testing.
		$GLOBALS['shortcode_tags'] = []; // phpcs:ignore.
		$this->_restore_shortcodes();
		$this->assertNotEmpty( $GLOBALS['shortcode_tags'] );
		$this->assertNotEmpty( $GLOBALS['shortcode_tags']['test'] );
		$this->assertNotEquals( $object, $GLOBALS['shortcode_tags']['test'] );
		$this->assertEquals( 'test', $GLOBALS['shortcode_tags']['test']->value );

		// Ignored for testing.
		$GLOBALS['shortcode_tags'] = $saved; // phpcs:ignore.

		$saved = $GLOBALS['_GET'];

		$GLOBALS['_GET'] = 'test';
		$this->_backup_global_vars();
		$this->assertNotEmpty( $this->_saved_global_vars );
		$this->assertEquals( 'test', $this->_saved_global_vars['_GET'] );
		$this->_restore_global_vars();

		$object->value   = 'test';
		$GLOBALS['_GET'] = [ 'test' => $object ];

		$this->_backup_global_vars();
		$object->value = 'modified';
		$this->assertEquals( 'modified', $GLOBALS['_GET']['test']->value );
		$this->assertNotEmpty( $this->_saved_global_vars );
		$this->assertNotEquals( 'test', $this->_saved_global_vars['_GET'] );
		$this->assertNotEquals( $object, $this->_saved_global_vars['_GET'] );

		$this->_restore_global_vars();
		$this->assertNotEmpty( $GLOBALS['_GET'] );
		$this->assertNotEquals( $object, $GLOBALS['_GET'] );
		$this->assertEquals( 'test', $GLOBALS['_GET']['test']->value );

		$GLOBALS['_GET'] = $saved;

	}

	/**
	 *  Test doing_it_wrong run
	 *
	 * @return void
	 */
	public function test_doing_it_wrong_run() {
		$this->doing_it_wrong_run( 'wp_add_privacy_policy_content' );
		$this->assertEmpty( $this->caught_doing_it_wrong );
	}

	/**
	 * Test expect doing_it_wrong
	 *
	 * @expectedIncorrectUsage test
	 */
	public function test_expect_doing_it_wrong() {
		$this->doing_it_wrong_run( 'test' );
	}

	/**
	 * Test constructor and setup_hooks.
	 *
	 * @return void
	 */
	public function test_assert_hooks() {

		$this->do_test_construct(
			Test_Construct::class,
			[
				[
					'name'     => 'filter1',
					'priority' => 10,
					'callback' => 'callable',
				],
				[
					'name'     => 'filter1',
					'callback' => 'callable',
				],
			]
		);

		$this->do_test_construct(
			function () {
				add_filter( 'closure', 'closure' );
			},
			[
				'closure' => 'exists',
			]
		);

		$filters = [
			[
				'shortcode',
				'name' => 'shortcode',
			],

			[
				'removed',
				'shortcode',
				'name' => 'remove_shortcode',
			],

			[
				'removed',
				'name'     => 'remove_all_filters',
				'priority' => 10,
			],

			[
				'name'     => 'filter1',
				'priority' => 100,
				'listener' => [ Test_Construct::get_instance(), 'test100' ],
			],

			[
				'name'     => 'filter1',
				'listener' => [ Test_Construct::class, 'test' ],
			],

			[
				'condition' => 'not',
				'name'      => 'filter_not',
				'callback'  => [ Test_Construct::class, 'filter_not' ],
			],

			'has_filter'         => 'exists',
			'remove_all_filters' => 'not',

			'shortcode-invalid'  => [ 'shortcode' ], // Invalid entry.

			'shortcode4'         => [ 'shortcode', 'not' ],
			'shortcode3'         => [
				'shortcode',
				'callback' => 'test',
			],
			'shortcode'          => [
				'test' => [ 'shortcode' ],
			],
			'shortcode1'         => [
				'test' => [ 'shortcode', 'function' ],
			],
			'shortcode2'         => [
				'test'  => [ 'shortcode', 'not' ],
				'test1' => [ 'shortcode', 'not', 'function' ],
			],
			'filter'             => 'test',
			'filter1'            => [
				[ Test_Construct::class, 'callable' ],
				'test',
				'test5'      => 5,
				'test10'     => [ 'not' ],
				'test20'     => [
					'not',
					'priority' => 20,
				],
				'test100'    => [ 'priority' => 100 ],
				'function'   => [ 'function', 'not' ],
				'function10' => [ 'function' ],
				'function11' => [
					'function',
					'priority' => 11,
				],
				'function12' => [
					'function',
					'priority' => 12,
					'not',
				],
				'callable'   => [ Test_Construct::get_instance(), 'callable' ],
			],
			'action'             => [
				'action' => [ 'action' ],
			],
			'run_custom_method'  => [
				'run_custom_method' => [ 'not' ],
			],
		];

		$exception = null;
		try {
			$this->do_test_construct( self::class, [] );
		} catch ( \Error $e ) {
			$exception = $e;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Must provide a valid list of $hooks to validate', $exception->getMessage() );

		$exception = null;
		try {
			$this->do_test_construct( null, $filters );
		} catch ( \Error $e ) {
			$exception = $e;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Must provide a valid class, instance, or closure', $exception->getMessage() );

		$exception = null;
		try {
			$this->do_test_construct( 'null', $filters );
		} catch ( \Error $e ) {
			$exception = $e;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Class not found null', $exception->getMessage() );

		$exception = null;
		try {
			$this->do_test_construct( self::class, $filters );
		} catch ( \Error $e ) {
			$exception = $e;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( sprintf( 'Class must implement singleton function %s::get_instance()', self::class ), $exception->getMessage() );

		$this->assert_hooks( $filters, Test_Construct::class );
		$this->assert_hooks( $filters, Test_Construct::get_instance() );

		$filters = [
			'filter1' => [
				'function'   => [ 'function', 'not' ],
				'function12' => [
					'function',
					'priority' => 12,
					'not',
				],
			],
		];

		Utility::invoke_hidden_method( $this, '_register_removed_hooks', [ $filters ] );
		Utility::invoke_hidden_method( \PMC\Unit_Test\Tests\Dummy\Test_Construct::get_instance(), '__construct' );
		$this->assert_hooks( $filters );

		$exception = null;
		try {
			$this->assert_hooks( [] );
		} catch ( \Error $e ) {
			$exception = $e;
		}
		$this->assertNotEmpty( $exception );
		$this->assertContains( 'Must provide a valid list of $hooks to validate', $exception->getMessage() );

		$filters['run_custom_method']['run_custom_method'] = 10;
		$this->assert_hooks(
			$filters,
			Test_Construct::class,
			[
				'run_custom_method',
				'method_not_exists',
				[ Test_Construct::get_instance(), 'callable' ],
			]
		);

		add_filter( 'test_hook_no_instance', '__return_true' );
		$this->assert_hooks( [ 'test_hook_no_instance' => '__return_true' ], true );
		$this->assert_hooks( [ 'test_hook_no_instance' => '__return_true' ], [ 'not-callable' ] );

		$hooks = [
			[
				'callback'  => 'callback',
				'condition' => 'removed',
				'name'      => 'test_remove',
				'priority'  => 10,
			],
		];
		remove_all_filters( 'test_remove' );
		Utility::invoke_hidden_method( $this, '_register_removed_hooks', [ $hooks ] );
		$this->assertEquals( 10, has_filter( 'test_remove', 'callback' ) );
		remove_all_filters( 'test_remove' );
	}

	/**
	 * Test private visibility
	 *
	 * @return void
	 */
	public function test_private_test_cases() {
		$this->tested_groups = [];
		$this->do_test( 'group_a' );
		$this->assertEquals( [ '_test_group_a' ], $this->tested_groups );

		$this->tested_groups = [];
		$this->do_test();
		$this->assertEquals( [ '_test_group_a', '_test_group_b' ], $this->tested_groups );
	}

	/**
	 * Simulate private test function for group a testings.
	 */
	private function _test_group_a() {
		$this->tested_groups[] = __FUNCTION__;
	}

	/**
	 * Simulate private test function for group b testings.
	 */
	private function _test_group_b() {
		$this->tested_groups[] = __FUNCTION__;
	}

	/**
	 * Test wp_die assertion
	 *
	 * @return void
	 */
	private function _test_assert_wp_die() {
		$this->assert_wp_die(
			function () {
				wp_die();
			}
		);
		$this->assert_wp_die(
			function () {
				wp_die( 'test' );
			},
			'test'
		);
	}


}
