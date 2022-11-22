<?php
/**
 * Define as trait to allow sharing code between PMC\Unit_Test\Base_Ajax & PMC\Unit_Test\Base
 *
 * Naming syntax:
 *
 * PMC Unit Test Framework extension methods
 *  - Shall use protected snake_case naming convention to avoid conflict naming with the official Unit Test Framework naming
 *  - The naming will indicate they are from PMC Unit Test Framework and not to confuse with camelCase from Unit Test Framework
 *  - eg. protected function assert_something( xyz );
 *
 * @package pmc-unit-test
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace PMC\Unit_Test\Traits;

use PMC\Unit_Test\Deprecated;
use PMC\Unit_Test\Utility;
use PMC\Unit_Test\Object_Cache;

/**
 * Trait Base.
 */
trait Base {

	/**
	 * @var array Default vars in class being tested
	 */
	protected $_default_vars = [];

	/**
	 * @var array Use to store backup of global vars
	 */
	protected $_saved_global_vars = [];

	/**
	 * @var array Default values of class vars in class being tested
	 */
	protected $_default_values;

	/**
	 * @var object The current mocker object
	 */
	protected $_mocker        = null;
	protected $_mock_services = [];

	/**
	 * @var string Default name of snapshot
	 */
	private $_default_snapshot_name = 'first';

	/**
	 * Magic function to overload and execute mocker class if name is prefix with mock_
	 * eg.  $this->mock_method, would call [ $mocker, method ]
	 * @param  string $name     The method name being call
	 * @param  array $arguments The array of arguments
	 * @return mixed
	 */
	public function __call( $name, array $arguments ) {
		if ( 'mock_' === substr( $name, 0, 5 ) ) {
			return $this->_dispatch_mocker( $name, $arguments );
		}
		throw new \Error( sprintf( 'Call to undefined method %s::%s', get_class( $this ), $name ) );
	}

	/**
	 * Function to dispatch the unit test mocker
	 * @param  string $method    The mock function to call
	 * @param  array  $arguments The array of arguments
	 * @return mixed             The value returned by mocked function
	 */
	protected function _dispatch_mocker( $method, array $arguments ) {

		$mocker = $this->_mocker;
		// First try the default mocker if method exists
		if ( ! method_exists( $this->_mocker, $method ) ) {
			if ( 'mock_' === substr( $method, 0, 5 ) ) {
				$method = substr( $method, 5 );
			}

			if ( ! method_exists( $this->_mocker, $method ) ) {
				$pairs          = explode( '_', $method, 2 );
				$service_method = 'mock';
				$service        = $pairs[0];
				if ( ! empty( $pairs[1] ) ) {
					$service_method = $pairs[1];
				}

				if ( isset( $this->_mock_services[ $service ] ) ) {
					$method = $service_method;
					$mocker = $this->_mock_services[ $service ];
					if ( ! method_exists( $mocker, $method ) && 'mock' !== $method ) {
						$method = 'mock_' . $method;
					}
				}
			}
		}

		if ( ! is_callable( [ $mocker, $method ] ) ) {
			throw new \Error( sprintf( 'Call to undefined method %s::%s', get_class( $mocker ), $method ) );
		}
		return call_user_func_array( [ $mocker, $method ], $arguments );

	}

	/**
	 * Register the default and service mockers
	 */
	protected function _register_mockers() {
		$this->mock = \PMC\Unit_Test\Mocks\Factory::get_instance()
			->set_test_object( $this )
			->init();

		if ( class_exists( 'PMC\Unit_Test\Mock\Mocker' ) ) {
			$this->_mocker = new \PMC\Unit_Test\Mock\Mocker( $this );
		}

	}

	/**
	 * @return \PMC\Unit_Test\Mocks\Factory object
	 */
	protected function mock() {
		return $this->mock;
	}

	/**
	 * Override default setup function to speed up testing
	 */
	public function setUp() { // phpcs:ignore

		$GLOBALS['wp_object_cache'] = new Object_Cache();

		// This must run first before anything else
		$this->_register_mockers();

		// trigger custom plugin to load before we initiate any test
		// This must run before parent::setUp()
		$this->_load_plugin();

		// to speed up unit test, we bypass files scanning on upload folder
		self::$ignore_files = true;
		parent::setUp();

		// We do not want to trigger any deprecated related errors in unit test
		remove_all_actions( 'deprecated_function_run' );
		remove_all_actions( 'deprecated_argument_run' );
		remove_all_actions( 'deprecated_hook_run' );

		// Provide verbose info about current test case that is running
		fwrite( STDERR, sprintf( "\n%s::%s ", get_class( $this ), $this->getName() ) );  // phpcs:ignore

		// We need to fix this filter to allow testing WPCOM_Legacy_Redirector::insert_legacy_redirect
		remove_all_filters( 'wpcom_legacy_redirector_allow_insert' );
		add_filter( 'wpcom_legacy_redirector_allow_insert', '__return_true' );

		$this->_backup_shortcodes();
		$this->_backup_global_vars();

		// Add flag to detect whether unit test override setUp properly
		$this->__setUp_called = true; // phpcs:ignore

		wp_cache_flush();

		// WP 5.5 ready
		if ( substr( getenv( 'WP_VERSION' ), 0, 3 ) >= '5.5' ) {
			if ( is_object( $GLOBALS['wp_rewrite'] ) && ! $GLOBALS['wp_rewrite']->using_permalinks() ) {
				$GLOBALS['wp_rewrite']->set_permalink_structure( '/%year%/%monthnum%/%category%/%postname%-%post_id%/' );
			}
		}

		Utility::unset_singleton( \PMC\EComm\Tracking::class );

	}

	/**
	 * @codeCoverageIgnore We won't be able to cover this code here since we're throwing an Error exception after unit test finished.
	 */
	protected function assertPostConditions() {  // phpcs:ignore
		parent::assertPostConditions();
		if ( empty( $this->__setUp_called ) ) {  // phpcs:ignore
			$msg = sprintf( 'The unit test class %s did not override function setUp() correctly.  See https://confluence.pmcdev.io/x/JAIeAw#PMCWPPHPUnitConfiguration-functionsetUp() for details.', static::class );
			throw new \Error( $msg );
		}
	}

	public function tearDown() { // phpcs:ignore
		// We need to dispose mocked resources once test is done to avoid conflict with other tests
		foreach ( $this->_mock_services as $mocker ) {
			if ( is_callable( [ $mocker, 'mock_dispose' ] ) ) {
				$mocker->mock_dispose();
			}
			if ( is_callable( [ $mocker, 'dispose' ] ) ) {
				$mocker->dispose();
			}
		}
		if ( is_callable( [ $this->_mocker, 'mock_dispose' ] ) ) {
			$this->_mocker->mock_dispose();
		}
		if ( is_callable( [ $this->_mocker, 'dispose' ] ) ) {
			$this->_mocker->dispose();
		}

		$this->_restore_shortcodes();
		$this->_restore_global_vars();
		$this->mock->reset();

		parent::tearDown();
		$GLOBALS['wp_object_cache']->flush();
	}

	/**
	 * @codeCoverageIgnore We won't be able to cover this code here since it is a static function override of the WP Unit test base class
	 */
	public static function tearDownAfterClass() { // phpcs:ignore
		if ( in_array( getenv( 'PMC_PHPUNIT_AUTO_CLEANUP' ), [ 'true', 'yes', true ], true ) ) {
			if ( function_exists( '_delete_all_data' ) ) {
				_delete_all_data();
			}
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Helper function to do backup of the global shortcode
	 */
	protected function _backup_shortcodes() {
		$this->_saved_shortcodes = [];
		foreach ( $GLOBALS['shortcode_tags'] as $shortcode => $callback ) {
			if ( is_object( $callback ) ) {
				$this->_saved_shortcodes[ $shortcode ] = clone( $callback );
			} else {
				$this->_saved_shortcodes[ $shortcode ] = $callback;
			}
		}

	}

	/**
	 * Helper function to restore the global shortcode
	 */
	protected function _restore_shortcodes() {
		if ( isset( $this->_saved_shortcodes ) ) {
			$GLOBALS['shortcode_tags'] = [];
			foreach ( $this->_saved_shortcodes as $shortcode => $callback ) {
				if ( is_object( $callback ) ) {
					$GLOBALS['shortcode_tags'][ $shortcode ] = clone( $callback );
				} else {
					$GLOBALS['shortcode_tags'][ $shortcode ] = $callback;
				}
			}
		}
	}

	/**
	 * Helper function to backup the global variables
	 * since 2019-09-20
	 * @Author Amit Gupta, Hau
	 */
	protected function _backup_global_vars() {

		// IMPORTANT: All global variables must be serializable in order to backup and restore
		$global_vars = [
			'_ENV',
			'_POST',
			'_GET',
			'_COOKIE',
			'_SERVER',
			'_FILES',
			'_REQUEST',
			'wp_scripts',
			'wp_styles',
			'wp_current_filter',
			'wp_query',
		];

		// We cannot cover this code here as it requires changes to the php.ini file
		// @codeCoverageIgnoreStart
		if ( ini_get( 'register_long_arrays' ) === '1' ) { // phpcs:ignore
			$global_vars = array_merge(
				$global_vars,
				[
					'HTTP_ENV_VARS',
					'HTTP_POST_VARS',
					'HTTP_GET_VARS',
					'HTTP_COOKIE_VARS',
					'HTTP_SERVER_VARS',
					'HTTP_POST_FILES',
				]
			);
		}
		// This should not required a comments, to fix in phpcs PmcWpVip ruleset
		// @codeCoverageIgnoreEnd

		$this->_saved_global_vars = [];
		foreach ( $global_vars as $var ) {
			if ( isset( $GLOBALS[ $var ] ) ) {
				// we want to do a deep clone, trick by using unserialize( serialize () ) call to simiulate clone.
				// data stored as object is more efficient thant large serialized string.
				if ( is_array( $GLOBALS[ $var ] ) || is_object( $GLOBALS[ $var ] ) ) {
					$this->_saved_global_vars[ $var ] = Utility::clone_object( $GLOBALS[ $var ] ); // phpcs:ignore
				} else {
					$this->_saved_global_vars[ $var ] = $GLOBALS[ $var ];
				}
			}
		}

	}

	/**
	 * Helper function to restore the global variables
	 */
	protected function _restore_global_vars() {
		if ( isset( $this->_saved_global_vars ) ) {
			foreach ( $this->_saved_global_vars as $var => $value ) {
				$GLOBALS[ $var ] = $value; // phpcs:ignore
			}
			unset( $this->_saved_global_vars );
		}
	}

	/**
	 * Override default setup function to speed up testing
	 */
	public function remove_added_uploads() {
		// To prevent all upload files from deletion, since set $ignore_files = true
		// we override the function and do nothing here
	}

	/**
	 * Helper function to test if a plugin is successfully loaded by checking it's singletone object
	 */
	protected function assert_plugin_loaded( $class ) {

		// Make sure the class exists
		$this->assertTrue( class_exists( $class ), sprintf( 'error loading plugin, class "%s" not found', $class ) );

		// Make sure the plugin instance exists
		$instance = Utility::get_hidden_static_property( $class, '_instance' );
		$this->assertNotEmpty( $instance, sprintf( 'error loading plugin, instance from class "%s" not found', $class ) );
		$instance = reset( $instance );
		$this->assertNotEmpty( $instance, sprintf( 'error loading plugin, instance from class "%s" is empty', $class ) );
		$this->assertInstanceOf( $class, $instance, sprintf( 'error loading plugin, instance is not of class "%s"', $class ) );

		return $instance;

	}

	/**
	 * Helper function to detect wp_redirect call and validating the URL location
	 * @param $expected_location The URL we're expecting the wp_redirect to
	 * @param $callback_function Callable function to trigger the wp_redirect
	 */
	protected function assert_redirect_to( $expected_location, $callback_function, $expected_status = false ) {
		$redirect_to     = false;
		$redirect_status = 0;
		$exception       = false;

		$callback_filter = function( $location, $status ) use ( &$redirect_to, &$redirect_status ) { // phpcs:ignore
			$redirect_to     = $location;
			$redirect_status = $status;
			throw new \Exception( 'assertRedirectTo' );
		};

		add_filter( 'wp_redirect', $callback_filter, 9999, 2 );

		try {
			call_user_func( $callback_function );
		} catch ( \Exception $ex ) {
			if ( 'assertRedirectTo' === $ex->getMessage() ) {
				$exception = $ex;
			}
		}

		remove_filter( 'wp_redirect', $callback_filter, 9999 );

		$this->assertNotEmpty( $exception, 'Cannot detect any call to wp_redirect' );
		$this->assertNotEmpty( $redirect_to, 'Cannot detect redirect to location' );

		$this->assertEquals( $expected_location, $redirect_to, 'Redirect location does not match' );

		if ( $expected_status ) {
			$this->assertEquals( $expected_status, $redirect_status, 'Redirect status does not match' );
		}

	}

	/**
	 * Helper function to detect if wp_redirect is triggered
	 * @param $callback_function Callable function that potential trigger the wp_redirect
	 */
	protected function assert_not_redirect( $callback_function ) {
		$exception       = false;
		$callback_filter = function() {
			throw new \Exception( 'assertNotRedirect' );
		};

		add_filter( 'wp_redirect', $callback_filter, 9999, 2 );

		try {
			call_user_func( $callback_function );
		} catch ( \Exception $ex ) {
			if ( 'assertNotRedirect' === $ex->getMessage() ) {
				$exception = $ex;
			}
		}

		remove_filter( 'wp_redirect', $callback_filter, 9999 );

		$this->assertEmpty( $exception, 'Expecting no call to wp_redirect' );

	}

	/**
	 * Method to get the default values of class and store in a var for the purpose
	 * of resetting them back to mimic fresh instantiation.
	 *
	 * @param object $class_instance
	 * @param string $snapshot_name
	 *
	 * @return void
	 */
	protected function _take_snapshot( object $class_instance, string $snapshot_name = 'first' ) : void {

		$vars       = $this->_default_vars;
		$class_name = get_class( $class_instance );

		if ( empty( $snapshot_name ) ) {
			$snapshot_name = $this->_default_snapshot_name;
		}

		for ( $i = 0; $i < count( $vars ); $i++ ) {

			$this->_default_values[ $class_name ][ $snapshot_name ][ $vars[ $i ] ] = Utility::get_hidden_property( $class_instance, $vars[ $i ] );

		}

	}

	/**
	 * Method to set the default values of class for the purpose
	 * of resetting them back to mimic fresh instantiation.
	 *
	 * @param object $class_instance
	 * @param string $snapshot_name
	 *
	 * @return void
	 */
	protected function _restore_snapshot( object $class_instance, string $snapshot_name = 'first' ) : void {

		$_GET  = [];
		$_POST = [];

		if ( empty( $snapshot_name ) ) {
			$snapshot_name = $this->_default_snapshot_name;
		}

		$class_name = get_class( $class_instance );

		if ( ! isset( $this->_default_values[ $class_name ][ $snapshot_name ] ) ) {
			return;
		}

		$default_values = $this->_default_values[ $class_name ][ $snapshot_name ];

		foreach ( $default_values as $var_name => $var_value ) {
			Utility::set_and_get_hidden_property( $class_instance, $var_name, $var_value );
		}

	}

	/**
	 * Overload function to allow manual bypass deprecated errors
	 * @codeCoverageIgnore Don't have a good way to trigger the code coverage at the moment
	 */
	public function expectedDeprecated() {  // phpcs:ignore
		$caught_deprecated = [];
		foreach ( $this->caught_deprecated as $caught ) {
			$caught = apply_filters( 'pmc_deprecated_function', $caught );
			if ( ! empty( $caught ) ) {
				$caught_deprecated[] = $caught;
			}
		}
		$this->caught_deprecated = $caught_deprecated;

		$caught_doing_it_wrong = [];
		foreach ( $this->caught_doing_it_wrong as $caught ) {
			$caught = apply_filters( 'pmc_doing_it_wrong', $caught );
			if ( ! empty( $caught ) ) {
				$caught_doing_it_wrong[] = $caught;
			}
		}
		$this->caught_doing_it_wrong = $caught_doing_it_wrong;

		parent::expectedDeprecated();
	}

	/**
	 * Method to help test _setup_hooks()/_unset_hooks() of a class
	 * Example of $hooks setup:
	 *
	 * $hooks = [
	 *     [
	 *         'type'     => 'shortcode',
	 *         'name'     => 'buy-now',
	 *         'priority' => false,
	 *         'listener' => [ $this->_instance, 'shortcode_output' ],
	 *     ],
	 *     [
	 *         'type'     => 'action',
	 *         'name'     => 'wp_head',
	 *         'priority' => 1,
	 *         'listener' => [ '_wp_render_title_tag' ],
	 *     ],
	 *     [
	 *         'type'     => 'filter',
	 *         'name'     => 'pmc_ga_event_tracking',
	 *         'priority' => 10,
	 *         'listener' => [ $this->_instance, 'add_event_tracking' ],
	 *     ],
	 * ];
	 *
	 * @param array  $hooks           Array containing hooks that are to be tested against listeners
	 * @param object $class_instance  Instance of the class being tested
	 * @param bool   $assert_negation Set this to TRUE if hook removal is being tested. Defaults to FALSE.
	 *
	 * @return void
	 */
	protected function _assert_hooks( array $hooks, object $class_instance, bool $assert_negation = false ) : void {

		if ( $assert_negation ) {
			array_walk(
				$hooks,
				function( &$item ) {
					$item['condition'] = 'not';
				}
			);
		}

		$this->do_test_construct(
			function() use ( $class_instance, $assert_negation ) {

				$class_method = ( true === $assert_negation ) ? '_unset_hooks' : '_setup_hooks';
				Utility::invoke_hidden_method( $class_instance, $class_method );

			},
			$hooks
		);

	}

	/**
	 * Helper to test _setup_hook() method of a class.
	 * Example of $hooks setup:
	 *
	 * $hooks = [
	 *     [
	 *         'type'     => 'shortcode',
	 *         'name'     => 'buy-now',
	 *         'priority' => false,
	 *         'listener' => [ $this->_instance, 'shortcode_output' ],
	 *     ],
	 *     [
	 *         'type'     => 'filter',
	 *         'name'     => 'pmc_ga_event_tracking',
	 *         'priority' => 10,
	 *         'listener' => [ $this->_instance, 'add_event_tracking' ],
	 *     ],
	 * ];
	 *
	 * @param array  $hooks          Array containing hooks that are to be tested against listeners
	 * @param object $class_instance Instance of the class being tested
	 *
	 * @return void
	 */
	public function assert_setup_hooks( array $hooks, object $class_instance ) : void {
		$this->_assert_hooks( $hooks, $class_instance, false );
	}

	/**
	 * Helper to test class _unset_hooks() method of a class
	 * Example of $hooks setup:
	 *
	 * $hooks = [
	 *     [
	 *         'type'     => 'action',
	 *         'name'     => 'wp_head',
	 *         'priority' => 1,
	 *         'listener' => [ '_wp_render_title_tag' ],
	 *     ],
	 *     [
	 *         'type'     => 'filter',
	 *         'name'     => 'pmc_ga_event_tracking',
	 *         'priority' => 10,
	 *         'listener' => [ $this->_instance, 'add_event_tracking' ],
	 *     ],
	 * ];
	 *
	 * @param array  $hooks          Array containing hooks that are to be tested against listeners
	 * @param object $class_instance Instance of the class being tested
	 *
	 * @return void
	 */
	public function assert_unset_hooks( array $hooks, object $class_instance ) : void {
		$this->_assert_hooks( $hooks, $class_instance, true );
	}

	/**
	 * This function should be override by each unit test case
	 * @codeCoverageIgnore There is no need to cover an empty function in abstract class
	 */
	protected function _load_plugin() {
	}

	public function doing_it_wrong_run( $function ) {

		$excludes = [
			'wp_add_privacy_policy_content',
			'WP_Block_Type_Registry::register',
		];

		if ( in_array( $function, (array) $excludes, true ) ) {
			return;
		}

		return parent::doing_it_wrong_run( $function );

	}

	/**
	 * Override the function to fix go_to method
	 * @param $url
	 */
	public function go_to( $url ) {
		global $wpdb;

		if ( is_numeric( $url ) ) {
			$post_ID = intval( $url );
			$url     = false;
		} elseif ( $url instanceof \WP_Post ) {
			$post_ID = $url->ID;
			$url     = false;
		} elseif ( is_string( $url ) ) {
			parent::go_to( $url );
			if ( get_post() ) {
				return;
			}
		}

		if ( empty( $post_ID ) ) {
			$post_ID = $wpdb->get_var( $wpdb->prepare( "select ID from $wpdb->posts WHERE guid=%s", trailingslashit( $url ) ) ); // phpcs:ignore
			if ( empty( $post_ID ) ) {
				$post_ID = url_to_postid( $url ); // phpcs:ignore
			}
		}

		if ( ! empty( $post_ID ) ) {
			$query_args = [
				'post_type' => get_post_type( $post_ID ),
			];
			switch ( $query_args['post_type'] ) {
				case 'attachment':
					$query_args['attachment_id'] = $post_ID;
					break;
				case 'page':
					$query_args['page_id'] = $post_ID;
					break;
				default:
					$query_args['p'] = $post_ID;
					break;
			}
			parent::go_to( add_query_arg( $query_args, home_url() ) );

			if ( ! empty( $url ) ) {
				// Fail safe, does need to be include in coverage
				$permalink = $url; // @codeCoverageIgnore
			} else {
				$permalink = get_permalink( $post_ID );
			}
			if ( false === strpos( $permalink, '?' ) ) {
				$parts                  = wp_parse_url( $permalink );
				$_SERVER['REQUEST_URI'] = isset( $parts['path'] ) ? $parts['path'] : '/';  // phpcs:ignore
				$GLOBALS['wp']->request = $_SERVER['REQUEST_URI']; // phpcs:ignore
			}

			return;

		}

		parent::go_to( $url );
	}

	/**
	 * Helper function to convert $callback object into string
	 * @param mixed $callback
	 * @return string|true
	 */
	protected function _sprint_callback( $callback ) {
		if ( is_string( $callback ) ) {
			return sprintf( '%s()', $callback );
		} elseif ( is_array( $callback ) && count( $callback ) === 2 ) {
			if ( isset( $callback[0] ) && is_object( $callback[0] ) ) {
				if ( isset( $callback[1] ) && is_string( $callback[1] ) ) {
					return sprintf( '%s::%s()', get_class( $callback[0] ), $callback[1] );
				}
			}
		} elseif ( is_callable( $callback ) ) {
			return sprintf( '%s()', get_class( $callback ) );
		}

		// we shouldn't reach this code if $callback is valid
		return print_r( $callback, true ); // phpcs:ignore
	}

	/**
	 * This helper function is use to translate short rules into proper rules for assert_hooks & _register_not_hooks
	 *
	 * note: WP treats actions as filters.  They are interchangeable: add_action => add_filter
	 *
	 * $hooks = [
	 *
	 *     // The translated hooks array will have this format as item, also accepted as the input array item
	 *     [
	 *         'condition' => 'not' | 'exist' | 'removed' // optional, default to 'exist'
	 *         'type'      => 'shortcode' | 'action' | 'filter', // optional, default to 'filter'
	 *         'name'      => 'hook-name',
	 *         'listener' | 'callback' => [ $instance | class_name::class, 'do_hook_name ] | 'function_name',
	 *         'priority'  => 10, // optional, default to 10
	 *         'not',       // special case, if found, treat as condition = 'not'
	 *         'shortcode', // special case, if found, treat as type = shortcode
	 *         'removed',    // special case, if found, treat as condition = 'removed'
	 *     ],
	 *
	 *
	 *      // These are the many short form of array rules to identify hook information
	 *
	 *     'name-exist' => 'exist',  // special case, check if filter exists
	 *     'name-not'   => 'not',     // special case, check if filter not exists
	 *
	 *     'filter-name'  => 'function_to_call',  // single registered method of $instance.  If $instance is null, treat this as plain php function
	 *
	 *     'filter-name1' => [ // multiple functions register to same filter
	 *         'function_name1',  // registered method of $instance.  If $instance is null, treat this as plain php function
	 *
	 *         'function_name2' => [  // registered function with additional options
	 *                  'priority' => 10, // filter register with priority 10
	 *                  'not',            // un-registered function, validate by check not exists
	 *                  'function',       // the registered php function, not a method of $instance
	 *                  'action',         // the registered filter is an action, default is filter
	 *                  'shortcode',      // the registered filter is a shortcode, default is filter
	 *                  'filter',         // the registered filter is a filter, default is filter
	 *              ],
	 *          'function...n' => [ ... ],
	 *
	 *      ],
	 *
	 *      'filter...n' => [ ... ],
	 *
	 * ];
	 *
	 * @param array  $hooks    The various array defining the hooks set
	 * @param object $instance Optional class object
	 * @return array           The translated structured hooks array
	 */
	private function _translate_hooks( array $hooks, $instance = null ) : array {
		$translated_hooks = [];

		foreach ( $hooks as $name => $data ) {

			if ( is_numeric( $name ) ) {

				$hook = (array) $data;

				if ( isset( $hook['listener'] ) ) {
					$hook['callback'] = $hook['listener'];
					unset( $hook['listener'] );
				}

				foreach ( [ 'not', 'removed' ] as $token ) {
					if ( empty( $hook['condition'] ) && in_array( $token, (array) $hook, true ) ) {
						$hook['condition'] = $token;
					}
				}

				if ( empty( $hook['type'] ) && in_array( 'shortcode', (array) $hook, true ) ) {
					$hook['type'] = 'shortcode';
				}

				if ( ! empty( $instance ) && ! empty( $hook['callback'] ) ) {

					if ( is_array( $hook['callback'] ) && count( $hook['callback'] ) === 2
						&& get_class( $instance ) === $hook['callback'][0] ) {
						$hook['callback'][0] = $instance;
					} elseif ( is_string( $hook['callback'] )
						&& ! is_callable( $hook['callback'] )
						&& is_callable( [ $instance, $hook['callback'] ] ) ) {
						$hook['callback'] = [ $instance, $hook['callback'] ];
					}

				}

				if ( ! empty( $hook['condition'] )
					&& 'not' === $hook['condition']
					&& ! empty( $hook['callback'] )
					&& ! isset( $hook['priority'] )
					) {
					// remove_filter always required priority to work, default value is 10
					$hook['priority'] = 10; // default priority if we check for not condition with callback
				}

				$translated_hooks[] = $hook;
				continue;

			}

			if ( in_array( 'shortcode', (array) $data, true ) ) {
				if ( ! isset( $data['callback'] ) ) {
					if ( ! in_array( 'not', (array) $data, true ) ) {
						// invalid entry
						continue;
					}
					$data['callback'] = 'callback';
				}
				$function = $data['callback'];
				unset( $data['callback'] );
				$data = [
					$function => $data,
				];
			}

			foreach ( (array) $data as $key => $value ) {

				$function     = $key;
				$is_function  = false;
				$is_not       = false;
				$priority     = false;
				$is_action    = false;
				$is_shortcode = false;
				$hook         = [
					'name' => $name,
				];

				if ( is_callable( $value ) ) {
					$function    = $value;
					$is_function = true;
				} elseif ( is_string( $value ) ) {
					$function = $value;
				} elseif ( is_numeric( $value ) ) {
					$priority = $value;
				} else {
					$info         = (array) $value;
					$priority     = isset( $info['priority'] ) ? $info['priority'] : false;
					$is_function  = in_array( 'function', (array) $info, true ) || isset( $info['function'] );
					$is_not       = in_array( 'not', (array) $info, true ) || isset( $info['not'] );
					$is_action    = in_array( 'action', (array) $info, true ) || isset( $info['action'] );
					$is_shortcode = in_array( 'shortcode', (array) $info, true ) || isset( $info['shortcode'] );
				}

				if ( empty( $instance ) ) {
					$is_function = true;
				}

				switch ( $function ) {
					case 'exist':
					case 'exists':
						$is_function = true;
						$function    = false;
						$is_not      = false;
						break;
					case 'not':
						$is_function = true;
						$function    = false;
						$is_not      = true;
						break;
				}

				if ( $is_not ) {
					$hook['condition'] = 'not';
				}

				if ( ! empty( $function ) ) {
					if ( $is_function ) {
						$hook['callback'] = $function;
					} else {
						$hook['callback'] = [ $instance, $function ];
					}
				}

				if ( $is_shortcode ) {
					$hook['type'] = 'shortcode';
				} else {
					if ( $is_action ) {
						$hook['type'] = 'action';
					}
					if ( $priority ) {
						$hook['priority'] = $priority;
					}
				}

				if ( ! empty( $instance )
					&& ! empty( $hook['callback'] )
					&& is_array( $hook['callback'] )
					&& count( $hook['callback'] ) === 2
					&& get_class( $instance ) === $hook['callback'][0] ) {

					$hook['callback'][0] = $instance;

				}

				if ( ! empty( $hook['condition'] )
					&& 'not' === $hook['condition']
					&& ! empty( $hook['callback'] )
					&& ! isset( $hook['priority'] )
				) {
					$hook['priority'] = 10; // default priority if we check for not condition with callback
				}

				$translated_hooks[] = $hook;

			} // foreach

		} // foreach

		return $translated_hooks;
	}


	/**
	 * Helper function to register the not filters for do_test_construct function
	 * Should not be called outside of this class
	 *
	 * @param array $hooks     @see _translate_hooks
	 * @param object $instance
	 */
	private function _register_removed_hooks( array $hooks, $instance = null ) : void {

		$hooks = $this->_translate_hooks( $hooks, $instance );

		foreach ( $hooks as $hook ) {
			if ( empty( $hook['condition'] ) || 'removed' !== $hook['condition'] ) {
				continue;
			}

			if ( isset( $hook['type'] ) && 'shortcode' === $hook['type'] ) {
				add_shortcode( $hook['name'], isset( $hook['callback'] ) ? $hook['callback'] : '_fake_callback' );
			} elseif ( empty( $hook['callback'] ) ) {
				add_filter( $hook['name'], '_fake_callback' );
			} elseif ( ! empty( $hook['priority'] ) ) {
				add_filter( $hook['name'], $hook['callback'], $hook['priority'] );
			} else {
				// remove_filter always require priority, default is 10
				// By default, the function translate hook would auto added the priority 10 if there is a call back.
				add_filter( $hook['name'], $hook['callback'] ); // @codeCoverageIgnore
			}

		} // foreach

	}

	/**
	 * Function to validate hooks
	 *
	 * @param array $hooks     @see _translate_hooks
	 * @param object $instance
	 */
	private function _validate_hooks( array $hooks, $instance = null ) : void {

		$hooks = $this->_translate_hooks( $hooks, $instance );

		foreach ( $hooks as $hook ) {

			// not / removed
			if ( isset( $hook['condition'] ) && in_array( $hook['condition'], [ 'not', 'removed' ], true ) ) {

				if ( isset( $hook['type'] ) && 'shortcode' === $hook['type'] ) {

					$this->assertFalse(
						shortcode_exists( $hook['name'] ),
						sprintf(
							'Failed to %3$s %1$s "%2$s"',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name'],
							( 'not' === $hook['condition'] ? 'not register' : 'unregister' )
						)
					);

				} elseif ( empty( $hook['callback'] ) ) {

					$this->assertFalse(
						has_filter( $hook['name'] ),
						sprintf(
							'Failed to %3$s %1$s "%2$s"',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name'],
							( 'not' === $hook['condition'] ? 'not register' : 'unregister' )
						)
					);

				} elseif ( ! empty( $hook['priority'] ) ) {

					$this->assertNotEquals(
						$hook['priority'],
						has_filter( $hook['name'], $hook['callback'] ),
						sprintf(
							'Failed to %5$s %3$s from %1$s "%2$s" with priority %4$d',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name'],
							$this->_sprint_callback( $hook['callback'] ),
							$hook['priority'],
							( 'not' === $hook['condition'] ? 'not register' : 'unregister' )
						)
					);

				} else {
					// remove_filter always require priority, default is 10
					// By default, the function translate hook would auto added the priority 10 if there is a call back.
					// @codeCoverageIgnoreStart
					$this->assertFalse(
						has_filter( $hook['name'], $hook['callback'] ),
						sprintf(
							'Failed to %4$s %3$s from %1$s "%2$s"',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name'],
							$this->_sprint_callback( $hook['callback'] ),
							( 'not' === $hook['condition'] ? 'not register' : 'unregister' )
						)
					);
					// Add comment to work around phpcs
					// @codeCoverageIgnoreEnd

				}

			} else {
				// EXISTS

				if ( isset( $hook['type'] ) && 'shortcode' === $hook['type'] ) {

					$this->assertTrue(
						shortcode_exists( $hook['name'] ),
						sprintf(
							'Failed to register shortcode "%1$s"',
							$hook['name']
						)
					);

					if ( ! empty( $hook['callback'] ) ) {

						$this->assertEquals(
							$hook['callback'],
							$GLOBALS['shortcode_tags'][ $hook['name'] ],
							sprintf(
								'Failed to register shortcode "%1$s" to %2$s',
								$hook['name'],
								$this->_sprint_callback( $hook['callback'] )
							)
						);

					}

				} elseif ( empty( $hook['callback'] ) ) {

					$this->assertTrue(
						has_filter( $hook['name'] ),
						sprintf(
							'Failed to register %1$s "%2$s"',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name']
						)
					);

				} elseif ( ! empty( $hook['priority'] ) ) {

					$this->assertEquals(
						$hook['priority'],
						has_filter( $hook['name'], $hook['callback'] ),
						sprintf(
							'Failed to register %1$s "%2$s" to %3$s with priority %4$d',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name'],
							$this->_sprint_callback( $hook['callback'] ),
							$hook['priority']
						)
					);

				} else {
					$this->assertNotFalse(
						has_filter( $hook['name'], $hook['callback'] ),
						sprintf(
							'Failed to register %1$s "%2$s" to %3$s',
							isset( $hook['type'] ) ? $hook['type'] : 'filter',
							$hook['name'],
							$this->_sprint_callback( $hook['callback'] )
						)
					);
				}

			}

		} // foreach

	}

	/**
	 * Helper method to do class construct testing
	 * - backup and save existing hooks
	 * - trigger class construct to run
	 * - validate hooks
	 * - restore existing saved hooks
	 *
	 * @param mixed $class_or_instance A callable object or class
	 * @param array $hooks             @see _translate_hooks
	 */
	public function do_test_construct( $class_instance_closure, array $hooks ) : void {
		Deprecated::get_instance()->warn( __FUNCTION__, '$this->assert_hooks( $hooks, $class_instance_closure )' );

		if ( empty( $class_instance_closure ) ) {
			throw new \Error( 'Must provide a valid class, instance, or closure' );
		}

		if ( empty( $hooks ) ) {
			throw new \Error( 'Must provide a valid list of $hooks to validate' );
		}

		$this->assert_hooks( $hooks, $class_instance_closure );
	}

	/**
	 * @param array $hooks                 @see _translate_hooks
	 * @param null $class_instance_closure A callable function, object, class, or closure
	 * @param array $maybe_invoke_methods  Invoke additional instance's methods as needed, eg. [ '_setup_hooks' ]
	 */
	public function assert_hooks( array $hooks, $class_instance_closure = null, array $maybe_invoke_methods = [] ) : void {

		if ( empty( $hooks ) ) {
			throw new \Error( 'Must provide a valid list of $hooks to validate' );
		}

		$instance = null;

		if ( ! empty( $class_instance_closure ) ) {
			if ( is_callable( $class_instance_closure ) ) {
				$instance = call_user_func( $class_instance_closure );
			} elseif ( is_object( $class_instance_closure ) ) {
				$instance = $class_instance_closure;
			} elseif ( is_string( $class_instance_closure ) ) {
				if ( ! class_exists( $class_instance_closure ) ) {
					throw new \Error( sprintf( 'Class not found %s', $class_instance_closure ) );
				}
				$class = new \ReflectionClass( $class_instance_closure );
				if ( ! $class->hasMethod( 'get_instance' ) ) {
					throw new \Error( sprintf( 'Class must implement singleton function %s::get_instance()', $class_instance_closure ) );
				}
				$instance = $class_instance_closure::get_instance();
			} else {
				$class_instance_closure = null;
			}
		}

		// backup existing hooks
		$hooks_saved = [];
		$globals     = array( 'wp_filter', 'wp_actions', 'wp_current_filter', 'shortcode_tags' );
		foreach ( $globals as $key ) {
			$hooks_saved[ $key ] = isset( $GLOBALS[ $key ] ) ? $GLOBALS[ $key ] : [];
			if ( ! empty( $class_instance_closure ) ) {
				$GLOBALS[ $key ] = []; // reset the global hooks
			}
		}

		$hooks = $this->_translate_hooks( $hooks, $instance );

		// We need to register the not filters to validate filter un-register when construct is trigger
		$this->_register_removed_hooks( $hooks, $instance );

		if ( is_callable( $class_instance_closure ) ) {
			call_user_func( $class_instance_closure );
		}

		if ( ! empty( $instance ) ) {
			// Run the construct to trigger wp events
			Utility::invoke_hidden_method( $instance, '__construct' );
			foreach ( $maybe_invoke_methods as $method ) {
				try {
					if ( is_callable( $method ) ) {
						call_user_func( $method );
					} else {
						Utility::invoke_hidden_method( $instance, $method );
					}
				} catch ( \Exception $ex ) {
					fwrite( STDERR, sprintf( "\nError invoking %s::%s\n", get_class( $instance ), $method ) );  // phpcs:ignore
				}
			}
		}

		// Validate the hooks
		$this->_validate_hooks( $hooks, $instance );

		// restore original hooks
		foreach ( $globals as $key ) {
			$GLOBALS[ $key ] = $hooks_saved[ $key ];
		}

	}

	/**
	 * Special function to test group of private test cases using pattern matching
	 *
	 * Usage:
	 *
	 * // Declaring the private tests function with prefix `_test_`
	 * private function _test_group_a_construct() { }
	 * private function _test_group_a_hooks() { }
	 * private function _test_group_b_construct() { }
	 * private function _test_group_b_hooks() {}
	 *
	 * // Grouping test together can be benefit when private tests case are depending on each other
	 *
	 * // Declare test function for group a
	 * public function test_group_a() {
	 *    $this->do_test( 'group_a' );
	 * }
	 *
	 * // Declare test function for group b
	 * public function test_group_b() {
	 *    $this->do_test( 'group_b' );
	 * }
	 *
	 * // Grouping all test into one giant test case can improve test performance
	 *
	 * // The above two test group and be consolidated into one giant test case
	 * // This declaration will test all private function with _test_prefix
	 * public function test_all() {
	 *    $this->do_test();
	 * }
	 *
	 * @param string $regx_patterns RegEx patterns of the test function to test
	 */
	protected function do_test( string $regex_patterns = '' ) : void {

		$class   = new \ReflectionClass( static::class );
		$methods = $class->getMethods( \ReflectionMethod::IS_PRIVATE );

		$methods = array_filter(
			$methods,
			function( $item ) use ( $regex_patterns ) {
				if ( static::class !== $item->class || substr( $item->name, 0, 6 ) !== '_test_' ) {
					return false;
				}

				if ( ! empty( $regex_patterns ) ) {
					return preg_match( '/' . $regex_patterns . '/', $item->name );
				}

				return true;

			}
		);

		foreach ( $methods as $method ) {
			$method->setAccessible( true );
			$method->invoke( $this );
		}

	}

	/**
	 * Helper function to detect wp_die call and validating the matching message if provided
	 * @param $expected_message  The expecting message to match
	 * @param $callback_function Callable function to trigger the wp_redirect
	 */
	protected function assert_wp_die( $callback_function, $expected_message = false ) {
		$detected_message = false;
		$exception        = false;

		$wp_die_handler = function( $message = false, $title = false, $args = [] ) use ( &$detected_message ) { // phpcs:ignore
			$detected_message = $message;
			throw new \Exception( 'assert_wp_die' );
		};

		$wp_die_filter = function() use ( $wp_die_handler ) {  // phpcs:ignore
			return $wp_die_handler;
		};

		add_filter( 'wp_die_handler', $wp_die_filter, 99999 ); // we want our filter to run last to override wp_die handler

		try {
			call_user_func( $callback_function );
		} catch ( \Exception $ex ) {
			if ( 'assert_wp_die' === $ex->getMessage() ) {
				$exception = $ex;
			}
		}

		remove_filter( 'wp_die', $wp_die_filter, 99999 );

		$this->assertNotEmpty( $exception, 'Cannot detect any call to wp_die' );
		if ( ! empty( $expected_message ) ) {
			$this->assertNotEmpty( $detected_message, sprintf( 'Cannot detect %s', $expected_message ) );
			$this->assertEquals( $expected_message, $detected_message, 'Message does not match' );
		}

	}

}
