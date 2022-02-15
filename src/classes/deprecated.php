<?php
// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
// phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace


namespace PMC\Unit_Test;

use PMC\Global_Functions\Traits\Singleton;

/**
 * Class Deprecated
 * @package PMC\Unit_Test
 */
final class Deprecated {
	use Singleton;

	public $stacks          = [];
	public $log_file        = false;
	public $wp_theme_folder = false;
	public $diff_info       = [];
	public $path_replaces   = [];

	protected function __construct() {
		$this->log_file        = getenv( 'PMC_PHPUNIT_DEPRECATED_LOG' );
		$this->wp_theme_folder = getenv( 'WP_THEME_FOLDER' );
		if ( $this->log_enabled() ) {
			register_shutdown_function( [ $this, 'shutdown' ] );
		}
		$path = realpath( __DIR__ . '/../..' );

		if ( ! empty( $this->wp_theme_folder ) ) {
			// If theme path is empty, there is no point of testing this code
			$this->path_replaces[ trailingslashit( $this->wp_theme_folder ) ] = ''; // @codeCoverageIgnore
		}

		if ( ! empty( $path ) ) {
			$this->path_replaces[ trailingslashit( $path ) ] = '';
		}

		$this->load_diff_file();
	}

	public function shutdown() {
		if ( ! $this->log_enabled() ) {
			return;
		}
		file_put_contents( $this->log_file, json_encode( array_values( $this->stacks ), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
	}

	public function log_enabled() : bool {
		return ! empty( $this->log_file );
	}

	public function load_diff_file( $file = false ) : array {
		if ( empty( $file ) ) {
			$file = getenv( 'PMC_COMMIT_DIFF_FILE' );
		}
		if ( empty( $file ) ) {
			$file = realpath( getenv( 'HOME' ) . '/commit.diff' );
		}
		if ( ! file_exists( $file ) ) {
			return [];
		}
		$this->diff_info = Diff_Parser::get_instance()->parse( $file );
		return $this->diff_info;
	}

	public function warn( string $function, string $new_syntax ) : void {
		$file  = '';
		$error = ! defined( 'IS_PMC' ) || ! IS_PMC;

		if ( empty( $error ) ) {
			foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 ) as $stack ) {

				if ( false !== preg_match( '@/tests/@', $stack['file'] ) ) {

					$info = [
						'file'       => $stack['file'],
						'line'       => $stack['line'],
						'function'   => $function,
						'new_syntax' => $new_syntax,
					];
					if ( ! empty( $info['file'] ) ) {
						$file = sprintf( "%s:%d\n", $info['file'], $info['line'] );
					}

					// ignore test file from pmc-unit-test
					if ( 1 === preg_match( '@/pmc-unit-test/@', $stack['file'] )
						&& 0 === preg_match( '@/test-deprecated.php$@', $stack['file'] )
					) {
						// No need to cover this code
						continue;  // @codeCoverageIgnore
					}

					$error = $this->error_if_new( $info );

					break;
				}
			}
		}

		if ( $error ) {
			$text = 'ERROR';
		} else {
			$text = 'WARNING';
		}
		$msg = sprintf( "\n\n%s%s: Deprecated function call \"%s\"\nPlease use new syntax: \"%s\"\n\n", $file, $text, $function, $new_syntax );

		if ( $error ) {
			throw new \Exception( $msg );
		} else {
			fwrite( STDERR, $msg );  // phpcs:ignore
		}

	}

	/**
	 * Throw error if deprecated code is found in new code diff
	 * @param array $info
	 * @return bool
	 */
	public function error_if_new( array &$info ) : bool {
		$error = false;

		foreach ( $this->path_replaces as $search => $replace ) {
			$info['file'] = str_replace( $search, $replace, $info['file'] );
		}

		$info['file'] = preg_replace( '@^.*?/pmc-plugins/package/@', '/pmc-plugins/', $info['file'] );
		$info['file'] = preg_replace( '@^.*?/pmc-plugins/@', '/pmc-plugins/', $info['file'] );
		$info['file'] = preg_replace( '@^/[^/]+/@', '', $info['file'] );

		if ( isset( $this->diff_info[ $info['file'] ] ) ) {
			if ( in_array( intval( $info['line'] ), (array) $this->diff_info[ $info['file'] ], true ) ) {
				$error = true;
			}
		}

		if ( $this->log_enabled() ) {
			$file                  = sprintf( "%s:%s\n", $info['file'], $info['line'] );
			$this->stacks[ $file ] = $info;
		}

		return $error;
	}

}
