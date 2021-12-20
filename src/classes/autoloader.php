<?php
/**
 * Autoloader for PHP classes inside Unit tests
 *
 * @author Amit Gupta <agupta@pmc.com>
 * @since 2019-01-31
 *
 */

namespace PMC\Unit_Test;

/**
 * @package pmc-unit-test
 */
class Autoloader {

	protected $_namespace = '';
	protected $_path      = '';

	/**
	 * @param  string $namespace The name space to auto trigger the class
	 * @param  string $path      The full path to the root folder representing the namespace
	 */
	public function __construct( string $namespace, string $path ) {
		$namespace = trim( $namespace, '\\' );
		$path      = rtrim( $path, '/' );

		if ( empty( $namespace ) || empty( $path ) ) {
			throw new \Exception( 'Error: namespace and path must be specified' );
		}

		$this->_namespace = $namespace . '\\';
		$this->_path      = $path . '/';
	}


	/**
	 * Static helper function to register a name space with a code path
	 * @param  string $namespace The name space to auto trigger the class
	 * @param  string $path      The full path to the root folder representing the namespace
	 * @return void
	 */
	public static function register( string $namespace, string $path ) : void {
		$instance = new Autoloader( $namespace, $path );
		spl_autoload_register( [ $instance, 'auto_load' ] );
	}

	public function auto_load( $resource = '' ) : void {

		$resource = trim( $resource, '\\' );

		if ( empty( $resource ) || strpos( $resource, '\\' ) === false || strpos( $resource, $this->_namespace ) !== 0 ) {
			//not our namespace, bail out
			return;
		}

		// Remove the root namespace from resource name, its of no use now
		$resource = substr( $resource, strlen( $this->_namespace ) );

		$resource = str_replace(
			[
				'_',
				'\\',
			],
			[
				'-',
				'/',
			],
			$resource
		);

		$resource = strtolower( $resource );

		$path = sprintf( '%s/%s.php', rtrim( $this->_path, '/\\' ), $resource );

		if ( file_exists( $path ) ) {
			require_once $path;
		} else {

			$file_prefix = 'class';

			$resource_parts = explode( '/', $path );

			$resource_parts[ count( $resource_parts ) - 1 ] = sprintf(
				'%s-%s',
				strtolower( $file_prefix ),
				$resource_parts[ count( $resource_parts ) - 1 ]
			);

			$path = implode( '/', $resource_parts );

			if ( file_exists( $path ) ) {
				require_once $path;
			}

		}

	}

}
