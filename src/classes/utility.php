<?php
/**
 * Utility class with utility methods for Unit Tests
 *
 * @author Amit Gupta <agupta@pmc.com>
 * @since 2016-12-13
 * @package pmc-unit-test
 */

declare( strict_types = 1 );

namespace PMC\Unit_Test;

use ErrorException;
use ReflectionClass;
use WP_Error;

/**
 * Class Utility.
 */
final class Utility {

	/**
	 * Utility method to call private/protected method of a class and return method result as returned by the said
	 * method This is a generic wrapper function to align with reflection class and not to be used directly.
	 *
	 * @param mixed  $object_or_class_name The object/class whose method is to be called.
	 * @param string $method_name The Name of the method to call.
	 * @param array  $parameters The Parameters to be passed to the hidden method being called.
	 *
	 * @return mixed Result returned by the hidden method being called.
	 */
	private static function _invoke_method(
		mixed $object_or_class_name,
		string $method_name,
		array $parameters = []
	): mixed {

		$object = null;

		if ( is_object( $object_or_class_name ) ) {
			$object     = $object_or_class_name;
			$class_name = $object::class;
		} else {
			$class_name = $object_or_class_name;
		}

		$o_reflection = new ReflectionClass( $class_name );

		$method = $o_reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * Utility method to call private/protected method of a class and return method result as returned by the said
	 * method
	 *
	 * @param object $object Object of the class whose method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 *
	 * @return mixed              Result returned by the hidden method being called.
	 */
	public static function invoke_hidden_method( object $object, string $method_name, array $parameters = [] ): mixed {

		return self::_invoke_method( $object, $method_name, $parameters );
	}

	/**
	 * Utility method to call private/protected static method of a class and return method result as returned by the
	 * said method
	 *
	 * @param string $class_name The object whose method is to be called.
	 * @param string $method_name The Name of the method to call.
	 * @param array  $parameters The Parameters to be passed to the hidden method being called.
	 *
	 * @return mixed              Result returned by the hidden method being called.
	 */
	public static function invoke_hidden_static_method(
		string $class_name,
		string $method_name,
		array $parameters = []
	): mixed {
		return self::_invoke_method( $class_name, $method_name, $parameters );
	}

	/**
	 * Utility method to get private/protected property of a class/object
	 * This is a generic wrapper function to align with reflection class and not to be use directly.
	 *
	 * @param mixed  $object_or_class_name The object/class whose property is to be accessed.
	 * @param string $property_name The name of the property to access.
	 *
	 * @return mixed                      Value of the hidden property being accessed.
	 */
	private static function _get_property( mixed $object_or_class_name, string $property_name ): mixed {

		$object = null;

		if ( is_object( $object_or_class_name ) ) {
			$object     = $object_or_class_name;
			$class_name = $object::class;
		} else {
			$class_name = $object_or_class_name;
		}

		$o_reflection = new ReflectionClass( $class_name );
		$property     = $o_reflection->getProperty( $property_name );
		$property->setAccessible( true );

		return $property->getValue( $object );
	}

	/**
	 * Utility method to get private/protected property of a class
	 *
	 * @param object $object The object whose property is to be accessed.
	 * @param string $property_name The name of the property to access.
	 *
	 * @return mixed                Value of the hidden property being accessed.
	 */
	public static function get_hidden_property( object $object, string $property_name ): mixed {
		return self::_get_property( $object, $property_name );
	}

	/**
	 * Utility method to get private/protected static property of a class
	 *
	 * @param string $class_name The Class whose static property is to be accessed.
	 * @param string $property_name The name of the property to access.
	 *
	 * @return mixed                 Value of the hidden property being accessed.
	 */
	public static function get_hidden_static_property( string $class_name, string $property_name ): mixed {
		return self::_get_property( $class_name, $property_name );
	}

	/**
	 * Utility method to get private/protected constant of a class
	 *
	 * Technically, this will return any constant defined in the class, but it is intended to be used to access
	 * private or protected constants.
	 *
	 * @param string $class_name The Class whose constant is to be accessed.
	 * @param string $constant_name The name of the constant to access.
	 *
	 * @return mixed                 Value of the hidden constant being accessed.
	 */
	public static function get_hidden_constant( string $class_name, string $constant_name ): mixed {
		$reflection_class     = new ReflectionClass( $class_name );
		$reflection_constants = $reflection_class->getConstants();

		return $reflection_constants[ $constant_name ];
	}

	/**
	 * Utility method to set private/protected property of an object/class
	 * This is a generic wrapper function to align with reflection class and not to be use directly.
	 *
	 * @param mixed  $object_or_class_name The object/class whose property is to be accessed.
	 * @param string $property_name The name of the property to access.
	 * @param mixed  $property_value The value to be set for the hidden property.
	 *
	 * @return mixed                      Value of the hidden property being accessed.
	 */
	private static function _set_and_get_property(
		mixed $object_or_class_name,
		string $property_name,
		mixed $property_value
	): mixed {

		$object = null;

		if ( is_object( $object_or_class_name ) ) {
			$object     = $object_or_class_name;
			$class_name = $object::class;
		} else {
			$class_name = $object_or_class_name;
		}

		$o_reflection = new ReflectionClass( $class_name );
		$property     = $o_reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $object, $property_value );

		return $property->getValue( $object );
	}

	/**
	 * Utility method to set private/protected property of a class/object
	 *
	 * @param object $object The object whose property is to be accessed.
	 * @param string $property_name The name of the property to access.
	 * @param mixed  $property_value The value to be set for the hidden property.
	 *
	 * @return mixed                Value of the hidden property being accessed.
	 */
	public static function set_and_get_hidden_property(
		object $object,
		string $property_name,
		mixed $property_value
	): mixed {
		return self::_set_and_get_property( $object, $property_name, $property_value );
	}

	/**
	 * Utility method to set private/protected static property of a class/object
	 *
	 * @param string $class_name The class whose static property is to be accessed.
	 * @param string $property_name The name of the static property to access.
	 * @param mixed  $property_value The value to be set for the hidden static property.
	 *
	 * @return mixed                Value of the hidden static property being accessed.
	 */
	public static function set_and_get_hidden_static_property(
		string $class_name,
		string $property_name,
		mixed $property_value
	): mixed {
		return self::_set_and_get_property( $class_name, $property_name, $property_value );
	}

	/**
	 * Return if a class has a property declared
	 *
	 * @param mixed  $object_or_class The object/class whose property is to be accessed.
	 * @param string $property_name The name of the property to access.
	 *
	 * @return boolean                    True if property exists.
	 */
	public static function has_property( mixed $object_or_class, string $property_name ): bool {

		if ( is_object( $object_or_class ) ) {
			$class_name = $object_or_class::class;
		} else {
			$class_name = $object_or_class;
		}

		$o_reflection = new ReflectionClass( $class_name );
		$properties   = $o_reflection->getProperties();
		$properties   = wp_list_pluck( $properties, 'name' );

		return in_array( $property_name, (array) $properties, true );
	}

	/**
	 * Utility method to capture output from a function
	 *
	 * @param Callable $callback The callback from which output is to be captured.
	 * @param array    $parameters Parameters to be passed to the $callback.
	 *
	 * @return mixed Output from callback.
	 *
	 * @throws \ErrorException If callback is invalid.
	 */
	public static function buffer_and_return( callable $callback, array $parameters = [] ): mixed {

		if ( ! is_callable( $callback ) ) {
			throw new ErrorException( sprintf( '%s::%s() expects first parameter to be a valid callback', self::class, __FUNCTION__ ) );
		}

		ob_start();

		call_user_func_array( $callback, $parameters );

		return trim( ob_get_clean() );
	}

	/**
	 * Utility method to capture output from a private/protected method of a class
	 *
	 * @param string|object $object_or_class_name The object/class whose method is to be called.
	 * @param string        $method_name The Name of the method to call.
	 * @param array         $parameters Parameters to be passed to the method.
	 *
	 * @return mixed Output from callback.
	 */
	public static function buffer_and_return_hidden_method(
		string|object $object_or_class_name,
		string $method_name,
		array $parameters = []
	): mixed {

		ob_start();

		self::_invoke_method( $object_or_class_name, $method_name, $parameters );

		return trim( ob_get_clean() );
	}

	/**
	 * Utility method to assert a specific exception is thrown by a callback.
	 *
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for multiple
	 * exceptions in varying scenarios as Sebastian Bergmann in his know-it-all wisdom doesn't
	 * deem it necessary despite people having pushed working code to add this functionality in the past.
	 *
	 * @param string   $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param callable $callback Callback which is expected to throw the Exception.
	 * @param array    $callback_parameters Callback parameters.
	 * @param string   $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_exception(
		string $expected_exception_class,
		callable $callback,
		array $callback_parameters = [],
		string $message = ''
	): mixed {

		$actual_exception = null;

		try {
			call_user_func_array( $callback, $callback_parameters );
		} catch ( \Exception $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Helper class to use by various assert exception class via the unit framework assertInstanceOf function
	 *
	 * @param string $expected The expected value.
	 * @param string $actual The actual value.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_instance_of( string $expected, string $actual, string $message = '' ): mixed {

		// @codeCoverageIgnoreStart Can't test non-existence of Class.
		if ( class_exists( \PHPUnit_Framework_Assert::class ) ) {

			return call_user_func_array(
				'\PHPUnit_Framework_Assert::assertInstanceOf',
				[

					$expected,
					$actual,
					$message,

				]
			);
		}

		// @codeCoverageIgnoreEnd

		return call_user_func_array(
			'\PHPUnit\Framework\Assert::assertInstanceOf',
			[

				$expected,
				$actual,
				$message,

			]
		);
	}

	/**
	 * Utility method to assert a specific exception is thrown by a private/protected method of a object/class.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for multiple
	 * exceptions in varying scenarios as Sebastian Bergmann in his know-it-all wisdom doesn't
	 * deem it necessary despite people having pushed working code to add this functionality in past.
	 * This helper function call the generic invoke_method that can operate on the object/class.
	 *
	 * @param string $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param mixed  $object_or_class_name object/class whose method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_exception_on_method(
		string $expected_exception_class,
		mixed $object_or_class_name,
		string $method_name,
		array $parameters = [],
		string $message = ''
	): mixed {
		$actual_exception = null;

		try {
			self::_invoke_method( $object_or_class_name, $method_name, $parameters );
		} catch ( \Exception $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Utility method to assert a specific exception is thrown by a private/protected method of a class.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for multiple
	 * exceptions in varying scenarios as Sebastian Bergmann in his know-it-all wisdom doesn't
	 * deem it necessary despite people having pushed working code to add this functionality in past.
	 *
	 * @param string $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param object $object The object whose method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_exception_on_hidden_method(
		string $expected_exception_class,
		object $object,
		string $method_name,
		array $parameters = [],
		string $message = ''
	): mixed {
		return self::assert_exception_on_method( $expected_exception_class, $object, $method_name, $parameters, $message );
	}

	/**
	 * Utility method to assert a specific exception is thrown by a private/protected static method of a class.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for multiple
	 * exceptions in varying scenarios as Sebastian Bergmann in his know-it-all wisdom doesn't
	 * deem it necessary despite people having pushed working code to add this functionality in past.
	 *
	 * @param string $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param string $class_name Name of the class whose static method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_exception_on_hidden_static_method(
		string $expected_exception_class,
		string $class_name,
		string $method_name,
		array $parameters = [],
		string $message = ''
	): mixed {

		$actual_exception = null;

		try {
			self::invoke_hidden_static_method( $class_name, $method_name, $parameters );
		} catch ( \Exception $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Utility method to assert a specific Error is thrown by a callback.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for errors
	 * in varying scenarios.
	 *
	 * This works with PHP 7+ only as the catchable errors are not available in previous versions.
	 *
	 * @param string   $expected_exception_class Fully qualified resource name of the Error which is expected.
	 * @param callable $callback Callback which is expected to throw the Error.
	 * @param array    $callback_parameters Callback parameters.
	 * @param string   $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_error(
		string $expected_exception_class,
		callable $callback,
		array $callback_parameters = [],
		string $message = ''
	): mixed {

		$actual_exception = null;

		try {
			call_user_func_array( $callback, $callback_parameters );
		} catch ( \Error $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Utility method to assert a specific Error is thrown by a private/protected method of a class.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for errors
	 * in varying scenarios.
	 *
	 * This works with PHP 7+ only as the catchable errors are not available in previous versions.
	 *
	 * @param string $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param mixed  $object_or_class_name object/class whose method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_error_on_method(
		string $expected_exception_class,
		mixed $object_or_class_name,
		string $method_name,
		array $parameters = [],
		string $message = ''
	): mixed {
		$actual_exception = null;

		try {
			self::_invoke_method( $object_or_class_name, $method_name, $parameters );
		} catch ( \Error $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Utility method to assert a specific Error is thrown by a private/protected method of a class.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for errors
	 * in varying scenarios.
	 *
	 * This works with PHP 7+ only as the catchable errors are not available in previous versions.
	 *
	 * @param string $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param object $object object/class whose method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_error_on_hidden_method(
		string $expected_exception_class,
		object $object,
		string $method_name,
		array $parameters = [],
		string $message = ''
	): mixed {


		$actual_exception = null;

		try {
			self::invoke_hidden_method( $object, $method_name, $parameters );
		} catch ( \Error $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Utility method to assert a specific Error is thrown by a private/protected static method of a class.
	 * This is needed because, stupidly enough, PHPUnit doesn't include a sane way to test for errors
	 * in varying scenarios.
	 *
	 * This works with PHP 7+ only as the catchable errors are not available in previous versions.
	 *
	 * @param string $expected_exception_class Fully qualified resource name of the Exception which is expected.
	 * @param string $class_name Name of the class whose static method is to be called.
	 * @param string $method_name Name of the method to call.
	 * @param array  $parameters Parameters to be passed to the hidden method being called.
	 * @param string $message Message to display if assertion fails.
	 *
	 * @return mixed
	 */
	public static function assert_error_on_hidden_static_method(
		string $expected_exception_class,
		string $class_name,
		string $method_name,
		array $parameters = [],
		string $message = ''
	): mixed {

		$actual_exception = null;

		try {
			self::invoke_hidden_static_method( $class_name, $method_name, $parameters );
		} catch ( \Error $e ) {
			$actual_exception = $e;
		}

		return self::assert_instance_of( $expected_exception_class, $actual_exception, $message );
	}

	/**
	 * Unset a Singleton instance.
	 *
	 * This helper is handy when you need to unset a singleton object. PMC Singleton
	 * (and the trait pattern as well) store an internal reference to each class
	 * (this allow the one instance to be returned on each request). This method is
	 * handy in tearDown() methods to clear out any singletons you've created in setUp().
	 *
	 * Example:
	 *
	 * public function setUp() {
	 *      $this->_my_class = PMC\Something\MyClass::get_instance();
	 * }
	 * public function tearDown() {
	 *      // unset the singleton instance so it's rebuilt on each test by class name
	 *      \PMC\Unit_Test\Utility::unset_singleton( \PMC\Something\MyClass::class );
	 *      // unset the singleton instance so it's rebuilt on each test by class name
	 *      \PMC\Unit_Test\Utility::unset_singleton( $this->_my_class );
	 *
	 * }
	 *
	 * @param mixed $object_or_class_name The object or class to unset the singleton, i.e. PMC\Something\MyClass.
	 *
	 * @return mixed
	 */
	public static function unset_singleton( mixed $object_or_class_name ): mixed {

		if ( is_object( $object_or_class_name ) ) {
			$class_name = $object_or_class_name::class;
		} else {
			$class_name = $object_or_class_name;
		}

		if ( ! empty( $class_name ) && class_exists( $class_name::class ) ) {
			// Unset the singleton instance so it's rebuilt on each test.
			$_instance = self::_get_property( $class_name, '_instance' );
			if ( isset( $_instance[ $class_name ] ) ) {
				$instance = $_instance[ $class_name ];
				unset( $_instance[ $class_name ] );
			}
			self::_set_and_get_property( $class_name, '_instance', $_instance );
			unset( $_instance );
		}

		if ( ! empty( $instance ) ) {
			return $instance;
		}
	}

	/**
	 * During testing, we may need to restore the old instance after we called ::unset_singleton
	 *
	 * @param object $instance Singleton instance we need to reset.
	 *
	 * @return void
	 */
	public static function restore_singleton( object $instance ): void {
		if ( ! empty( $instance ) && is_object( $instance ) ) {
			$class_name               = $instance::class;
			$_instance                = self::_get_property( $class_name, '_instance' );
			$_instance[ $class_name ] = $instance;
			self::_set_and_get_property( $class_name, '_instance', $_instance );
			unset( $_instance );
		}
	}

	/**
	 *  Unset a Singleton instance.
	 *
	 *  This helper is handy when you need to unset a singleton object. PMC Singleton
	 *  (and the trait pattern as well) store an internal reference to each class
	 *  (this allow the one instance to be returned on each request). This method is
	 *  handy in tearDown() methods to clear out any singletons you've created in setUp().
	 *
	 * @TODO: should remove once removed from other unit test.
	 *
	 * @param string  $class_name Name of the class whose static method is to be called.
	 * @param ?object $instance Instance of the class to be reset.
	 *
	 * @return void
	 */
	public static function unset_singleton_instance( string $class_name, ?object $instance = null ): void {
		self::unset_singleton( $class_name );
		if ( is_object( $instance ) ) {
			self::unset_singleton( $instance );
		}
	}

	/**
	 * Return the xml content from current mocked custom feed
	 */
	public static function simulate_render_custom_feed(): string {
		$bufs     = false;
		$template = \PMC_Custom_Feed::get_instance()->get_single_template_file( false );
		if ( ! empty( $template ) ) {
			$bufs = \PMC::render_template( $template );
		}

		return $bufs;
	}

	/**
	 * Return the html result triggered by get_header & get_footer
	 *
	 * @return string
	 */
	public static function simulate_wp_script_render(): string {
		// simulate wp script render.
		ob_start();

		// @see get_header & get_footer.
		do_action( 'get_header' );
		$templates = [
			'header.php',
		];
		// We need to use require and not require_once.
		locate_template( $templates, true, false );

		do_action( 'get_footer' );
		$templates = [
			'footer.php',
		];
		locate_template( $templates, true, false );

		return ob_get_clean();
	}

	/**
	 * This filter allows you to setup a mock HTTP request that you can
	 * use in your unit tests. To get started, please refer to README.md file
	 * within this plugin for details and usage examples.
	 *
	 * Note: This filter is automatically loaded when loading this plugin to use
	 * in unit tests. You DO NOT need to implement this filter in bootstrap.php.
	 *
	 * @param false|array|WP_Error $response Whether to preempt an HTTP request's return value. Default false.
	 * @param array                $request HTTP request arguments.
	 * @param string               $url The request URL.
	 *
	 * @uses   pre_http_request
	 *
	 * @return boolean|array
	 */
	public static function filter_pre_http_request(
		false|array|WP_Error $response,
		array $request,
		string $url
	): bool|array {
		$mocks = apply_filters( 'pmc_unit_test__http_mocks', [] );

		if ( is_array( $mocks ) ) {
			foreach ( $mocks as $mock_group ) {
				if ( ! is_array( $mock_group ) ) {
					continue;
				}

				foreach ( $mock_group as $mock ) {
					if ( ! is_array( $mock ) ) {
						continue;
					}

					/*
					 * Mock URLs end in test=test_my_method. Here we want to
					 * break up the mock URL at the `?` to match the beginning (remote endpoint)
					 * and the query string (our test=test_my_method). We aren't
					 * concerned about stuff in the middle as the test=test_my_method
					 * is the unique identifier.
					 *
					 * @var array
					 */
					$mock_url = explode( '?', (string) $mock['request']['url'] );

					/*
					* preg_match below matches the beginning of the URL (API endpoint)
					* and the end of the URL (our test=test_my_method identifier)
					* ref: https://regex101.com/r/9ccFd0/2
					*/
					if (
						2 === count( $mock_url ) &&
						preg_match( '#^' . $mock_url[0] . '(.*)' . $mock_url[1] . '$#i', $url ) &&
						(
							empty( $mock['request']['method'] ) ||
							strtolower( (string) $request['method'] ) === strtolower( (string) $mock['request']['method'] )
						)
					) {

						if ( isset( $mock['response']['body'] ) ) {
							$body = $mock['response']['body'];
						} elseif ( isset( $mock['response']['file'] ) ) {
							// add suupport to read data from file on demand to avoid large chunks data all read into memory at one time.
							$body = file_get_contents( $mock['response']['file'] ); // phpcs:ignore
						} else {
							$body = null;
						}

						/*
						* Note: wp_remote_get returns Requests_Response_Headers object for the headers fields.
						* Return only data we need, wp_remote_get returns additional data that's not efficient to
						* mock and recreate the same data here.
						*/
						return [
							'headers'  => new \Requests_Response_Headers( ! empty( $mock['response']['headers'] ) ? (array) $mock['response']['headers'] : [] ),
							'body'     => $body,
							'response' => ! empty( $mock['response']['status'] ) ? (array) $mock['response']['status'] : [
								'code'    => 200,
								'message' => 'OK',
							],
						];
					}
				}
			}
		}

		// If filter is `true` no outside http requests will be made.
		return apply_filters( 'pmc_unit_test__force_http_mock', $response );
	}

	/**
	 * Simulate as mobile user.
	 *
	 * @return void
	 *
	 * @deprecated
	 *
	 * @codeCoverageIgnore Ignoring coverage on this one because this is only temporary for now. This will soon be
	 *     removed as it has been deprecated.
	 */
	public static function simulate_is_mobile(): void {

		\PMC\Unit_Test\Utility::deprecated( __FUNCTION__, '$this->mock->device( \'mobile\' )' );

		\PMC\Unit_Test\Mocks\Factory::get_instance()->device->mock( 'mobile' );
	}

	/**
	 * Simulate as desktop user.
	 *
	 * @return void
	 *
	 * @deprecated
	 *
	 * @codeCoverageIgnore Ignoring coverage on this one because this is only temporary for now. This will soon be
	 *     removed as it has been deprecated.
	 */
	public static function simulate_is_desktop(): void {

		\PMC\Unit_Test\Utility::deprecated( __FUNCTION__, '$this->mock->device( \'desktop\' )' );

		\PMC\Unit_Test\Mocks\Factory::get_instance()->device->mock( 'desktop' );
	}

	/**
	 * To remove all simulation.
	 *
	 * @return void
	 * @deprecated
	 *
	 * @codeCoverageIgnore Ignoring coverage on this one because this is only temporary for now. This will soon be
	 *     removed as it has been deprecated.
	 */
	public static function restore_device_simulation(): void {

		\PMC\Unit_Test\Utility::deprecated( __FUNCTION__, '$this->mock->device->reset()' );

		\PMC\Unit_Test\Mocks\Factory::get_instance()->device->reset();
	}

	/**
	 * Retrieve the singleton instance from a class
	 *
	 * This helper is handy when you need to retrieve a singleton object. PMC Singleton
	 * (and the trait pattern as well) store an internal reference to each class
	 * (this allow the one instance to be returned on each request). This method is
	 * handy to test plugin activation without trigger plugin activation.
	 *
	 * Example:
	 *
	 * $instance = \PMC\Unit_Test\Utility::get_instance( \PMC\MyClass:class );
	 * $this->assertNotEmpty( $instance, 'Plugin not yet activated' );
	 *
	 * @param string $class_name The full namespaced class name, i.e. \PMC\Something\MyClass::class.
	 *
	 * @return object|null
	 */
	public static function get_instance( string $class_name ): object|null {
		$instances = self::get_hidden_static_property( $class_name, '_instance' );

		return $instances[ $class_name ] ?? null;
	}


	/**
	 * This helper function is to simulate the php headers_list function
	 * To return the HTTP headers being queued. Because in unit test, the header() call will cause
	 * warning error to throw and headers_list would not work;  We add this function here to take advantage
	 * of the php warning errors and use debug_backtrace to help detect and capture the values from header() calling
	 *
	 * Example:
	 *
	 * $headers = Utitlity::headers_list( function() {
	 *    header( 'Header: value' );
	 *  } );
	 *
	 * $this->assertEquals( [ 'Header: value' ], $headers );
	 *
	 * @param Callable $callback Callback function.
	 *
	 * @return array
	 */
	public static function headers_list( callable $callback ): array {

		$headers = [];

		// phpcs:ignore
		set_error_handler(
			function () use ( &$headers ) {
				// phpcs:ignore
				foreach ( debug_backtrace() as $item ) {
					if ( isset( $item['function'] ) && 'header' === $item['function'] ) {
						$headers[] = $item['args'][0];
					}
				}

				return true;
			},
			E_WARNING
		);

		ob_flush();
		call_user_func( $callback );
		restore_error_handler();

		return $headers;
	}

	/**
	 * Clone an object
	 *
	 * @param object $object Object for cloning.
	 *
	 * @return mixed
	 */
	public static function clone_object( object $object ): mixed {
		return unserialize( serialize( $object ), [ 'allowed_classes' => true ] );  // phpcs:ignore
	}

	/**
	 * Issue deprecation warning
	 *
	 * @param string $function Function that is deprecated.
	 * @param string $new_syntax Replacement syntax.
	 */
	public static function deprecated( string $function, string $new_syntax ): void {
		Deprecated::get_instance()->warn( $function, $new_syntax );
	}
}
