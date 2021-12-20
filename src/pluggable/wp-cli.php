<?php
/**
 * Defined some dummy classes to allow WP-CLI unit testing
 */

// @codeCoverageIgnoreStart
if ( ! class_exists( 'WP_CLI' ) ) {

	class WP_CLI {
		public static $last_called = false;
		public static $registered_commands = [];
		public static function add_command( $name, $callable, $args = array() ) {
			if ( is_callable( $callable ) || class_exists( $callable ) ) {
				static::$registered_commands[ $name ] = $callable;
			} else {
				throw new Exception( 'Cannot register wp command: ' . $name );
			}
		}

		public static function log( $msg ) {
			static::$last_called = __FUNCTION__;
			echo "{$msg}\n";
		}

		public static function line( $msg ) {
			static::$last_called = __FUNCTION__;
			echo "{$msg}\n";
		}

		public static function error( $msg ) {
			static::$last_called = __FUNCTION__;
			echo "Error: {$msg}\n";
		}

		public static function success( $msg ) {
			static::$last_called = __FUNCTION__;
			echo "Success: {$msg}\n";
		}

		public static function warning( $msg ) {
			static::$last_called = __FUNCTION__;
			echo "Warning: {$msg}\n";
		}

		public static function get_config() {
			return 'test-site';
		}

		public function get_runner() {
			return (object)[
				'arguments' => [ 'command', 'sub-command' ],
			];
		}

	}
}

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	abstract class WP_CLI_Command {}
}

if ( ! class_exists( 'WPCOM_VIP_CLI_Command' ) ) {

	class WPCOM_VIP_CLI_Command extends WP_CLI_Command {

		public static $last_called = false;
		public static $callback = null;

		protected function stop_the_insanity() {
			static::$last_called = __FUNCTION__;
			if ( ! empty( static::$callback ) ) {
				call_user_func( static::$callback, __FUNCTION__ );
			}
		}

		protected function start_bulk_operation() {
			static::$last_called = __FUNCTION__;
			if ( ! empty( static::$callback ) ) {
				call_user_func( static::$callback, __FUNCTION__ );
			}
		}

		protected function end_bulk_operation() {
			static::$last_called = __FUNCTION__;
			if ( ! empty( static::$callback ) ) {
				call_user_func( static::$callback, __FUNCTION__ );
			}
		}
	}
}

// @codeCoverageIgnoreEnd
