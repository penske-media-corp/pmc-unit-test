<?php
/**
 * Mocker class for WP Requests.
 *
 * We intercept all wp remote call by override the default transport
 * by override the hidden static variable \Requests::$transports to this custom class
 *
 * All mocked method are prefix with "mock_" to prevent potential function name collision with Requests_Transport_cURL
 *
 * @author Hau Vong <hvong@pmc.com>
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Mocks;

use SimplePie;

/**
 * Class Http.
 */
class Http implements \PMC\Unit_Test\Interfaces\Mocker {

	/**
	 * Curl request handler
	 *
	 * @var \WpOrg\Requests\Transport\Curl
	 */
	protected static $_curl;

	const FILTER_REMOTE_GET = 'pmc_mock_http_remote_get';
	const MOCK_SERVICE      = 'http';
	const ACTION_PRIORITY   = PHP_INT_MAX;

	// IMPORTANT!!! We need to use static variables here since we can't access WP created instance
	protected static $_transports_stored  = [];
	protected static $_mock_match         = [];
	protected static $_mock_next_match    = [];
	protected static $_mock_next_queues   = [];
	protected static $_default_404        = false;
	protected static $_default_404_verbal = false;
	protected static $_instance           = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$_curl = new \WpOrg\Requests\Transport\Curl();
	}

	public function provide_service() {
		return static::MOCK_SERVICE;
	}

	/**
	 * Helper function to intercept the transports class
	 * @return [type] [description]
	 */
	public function intercept_transport() {
		if ( empty( static::$_transports_stored ) ) {
			static::$_transports_stored = \PMC\Unit_Test\Utility::get_hidden_static_property( 'Requests', 'transports' );
			// force the transport to use this transport class
			\PMC\Unit_Test\Utility::set_and_get_hidden_static_property( 'Requests', 'transports', [ self::class ] );

			// Reset the caching entry to force a new transport to initialize and lookup
			\PMC\Unit_Test\Utility::set_and_get_hidden_static_property( 'Requests', 'transport', [] );

			add_action( 'wp_feed_options', [ $this, 'action_wp_feed_options' ], self::ACTION_PRIORITY, 2 );
		}
		return $this;
	}

	/**
	 * Alias function
	 */
	public function enable() {
		return $this->intercept_transport();
	}

	/**
	 * Alias function
	 */
	public function disable() {
		return $this->reset();
	}

	/**
	 * Clean out resource
	 * @mock http_dispose
	 * @param  string $url The url to mock
	 * @param  mixed $data See README.md doc for details
	 * @return void
	 */
	public function dispose() {
		return $this->reset();
	}

	public function default_not_found( $enable = true, $verbal = true ) {
		static::$_default_404        = $enable;
		static::$_default_404_verbal = $verbal;
		if ( $enable ) {
			return $this->intercept_transport();
		}
		return $this;
	}

	/**
	 * Mock the url
	 * @mock http
	 * @param  string $url The url to mock
	 * @param  mixed $data See README.md doc for details
	 * @return Requests
	 */
	public function mock( $url = false, $data = false ) : self {
		$this->intercept_transport();

		if ( false === $url ) {
			return $this;
		}

		if ( is_object( $data ) || is_string( $data ) ) {
			$data = [
				'body' => $data,
			];
		}

		static::$_mock_match[ $url ] = (array) $data;
		return $this;
	}

	/**
	 * Remove the mocked url from the mocked list via mock function
	 * @mock http_remove
	 * @param  string $url The url to remove
	 * @return Requests
	 */
	public function remove( $url ) : self {
		unset( static::$_mock_match[ $url ] );
		return $this;
	}

	/**
	 * Mock the request once, then remove
	 * @mock http_once
	 * @param  string $url The url to mock
	 * @param  mixed $data See README.md doc for details
	 * @return Requests
	 */
	public function once( $url, $data ) : self {
		$this->intercept_transport();

		if ( is_object( $data ) || is_string( $data ) ) {
			$data = [
				'body' => $data,
			];
		}
		$data              = (array) $data;
		$data['mock_once'] = true;

		return $this->mock( $url, $data );
	}

	/**
	 * Mock the next request once, then remove
	 * @mock http_next
	 * @param  string $url The url to mock
	 * @param  mixed $data See README.md doc for details
	 * @return Requests
	 */
	public function next( $url, $data ) : self {
		$this->intercept_transport();

		if ( is_object( $data ) || is_string( $data ) ) {
			$data = [
				'body' => $data,
			];
		}
		if ( '*' === $url || empty( $url ) ) {
			// highest priority fifo queues
			static::$_mock_next_queues[] = (array) $data;
		} else {
			static::$_mock_next_match[ $url ] = (array) $data;
		}
		return $this;
	}

	/**
	 * Restore all Requests transports to their default
	 * @mock http_reset
	 * @return Requests
	 */
	public function reset() : self {
		if ( ! empty( static::$_transports_stored ) ) {
			// Restore the original transport
			\PMC\Unit_Test\Utility::set_and_get_hidden_static_property( 'Requests', 'transports', static::$_transports_stored );

			// Reset the caching entry to force a new transport to initialize and lookup
			\PMC\Unit_Test\Utility::set_and_get_hidden_static_property( 'Requests', 'transport', [] );

			remove_action( 'wp_feed_options', [ $this, 'action_wp_feed_options' ], self::ACTION_PRIORITY );
		}

		static::$_mock_match         = [];
		static::$_mock_next_match    = [];
		static::$_mock_next_queues   = [];
		static::$_transports_stored  = [];
		static::$_default_404        = false;
		static::$_default_404_verbal = false;

		return $this;
	}

	/**
	 * Override the parent class function to intercept the request
	 *
	 * @see Requests_Transport_cURL::request() for details
	 *
	 * @param string $url URL to request
	 * @param array $headers Associative array of request headers
	 * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
	 * @param array $options Request options, see {@see Requests::response()} for documentation
	 * @return string Raw HTTP result
	 */
	public function request( $url, $headers = [], $data = [], $options = [] ) {

		$mock = apply_filters( 'pmc_pre_mock_http', false, $url, $headers, $data, $options );

		if ( empty( $mock ) ) {
			if ( ! empty( static::$_mock_next_queues ) ) {
				$mock = array_shift( static::$_mock_next_queues );
			} elseif ( isset( static::$_mock_next_match[ $url ] ) ) {
				$mock = static::$_mock_next_match[ $url ];
				unset( static::$_mock_next_match[ $url ] );
			} elseif ( isset( static::$_mock_match[ $url ] ) ) {
				$mock = static::$_mock_match[ $url ];
				if ( isset( $mock['mock_once'] ) && $mock['mock_once'] ) {
					unset( static::$_mock_match[ $url ] );
				}
			} elseif ( isset( static::$_mock_match['*'] ) ) {
				$mock = static::$_mock_match['*'];
				if ( isset( $mock['mock_once'] ) && $mock['mock_once'] ) {
					unset( static::$_mock_match['*'] );
				}
			}
		}

		$mock = apply_filters( 'pmc_mock_http', $mock, $url, $headers, $data, $options );

		$response_body    = '';
		$response_headers = '';

		if ( ! empty( $mock ) ) {

			if ( isset( $mock['raw'] ) ) {
				if ( file_exists( $mock['raw'] ) ) {
					return file_get_contents( $mock['raw'] ); // phpcs:ignore
				}
				return $mock['raw'];
			}

			if ( isset( $mock['headers'] ) ) {
				$response_headers = implode( "\n", (array) $mock['headers'] );
			}

			if ( isset( $mock['body'] ) ) {
				$response_body = $mock['body'];
			}

			if ( ! is_string( $response_body ) ) {

				if ( is_callable( $response_body ) ) {
					$response_body = call_user_func_array( $response_body, [ &$response_headers, $url, $headers, $data, $options ] );

					// magic key word for passthrough and retrieve from the remote server
					if ( '__remote_get' === $response_body ) {
						$result = self::$_curl->request( $url, $headers, $data, $options );
						return apply_filters( self::FILTER_REMOTE_GET, $result, $url, $headers, $data, $options );
					}

					$response_headers = implode( "\n", (array) $response_headers );
				}
				if ( ! is_string( $response_body ) ) {
					$response_body = wp_json_encode( $response_body );
				}

			} elseif ( isset( $mock['file'] ) && file_exists( $mock['file'] ) ) {
				$response_body = file_get_contents( $mock['file'] ); // phpcs:ignore
			}

			if ( empty( $response_headers ) ) {
				$response_headers = 'HTTP/1.1 200 OK';
			}

			return $response_headers . "\r\n\r\n" . $response_body;

		}

		// Backward compatibility rules lookup
		$result = \PMC\Unit_Test\Utility::filter_pre_http_request( null, null, $url );
		if ( is_array( $result ) ) {
			// Using filter for mocking is deprecated, no longer support for improvement
			// @codeCoverageIgnoreStart
			$response_headers = sprintf( 'HTTP/1.1 %d %s', $result['response']['code'], $result['response']['message'] );
			foreach ( $result['headers']->getAll() as $key => $values ) {
				foreach ( $values as $v ) {
					$response_headers .= sprintf( "\n%s: %s", $key, $v );
				}
			}
			if ( ! empty( $result['body'] ) ) {
				$response_body = $result['body'];
			}
			return $response_headers . "\r\n\r\n" . $response_body;
			// @codeCoverageIgnoreEnd
		}

		if ( static::$_default_404 ) {
			if ( static::$_default_404_verbal ) {
				fwrite( STDERR, sprintf( "\nRequest not mocked: %s\n", $url ) ); // phpcs:ignore
			}
			return sprintf( "HTTP/1.1 404 Not Found\r\n\r\nRequest not mocked: %s", $url );
		}

		$result = self::$_curl->request( $url, $headers, $data, $options );
		return apply_filters( self::FILTER_REMOTE_GET, $result, $url, $headers, $data, $options );

	}

	/**
	 * Test HTTP request
	 *
	 * This is just a wrapper for \WpOrg\Requests\Transport\Curl::test().
	 *
	 * @param  array<string,bool> $capabilities  Optional. Associative array of capabilities to test against, i.e. `['<capability>' => true]`.
	 * @return bool                               Whether the transport can be used.
	 */
	public static function test( $capabilities = [] ) {
		return self::$_curl::test( $capabilities );
	}

	/**
	 * Action to support mocking for fetch_feed
	 * @param SimplePie $feed
	 * @param $url
	 * @return void
	 */
	public function action_wp_feed_options( SimplePie &$feed, $url ) {
		if ( ! empty( $url ) ) {
			$result = \Requests::get( $url );
			$feed->set_raw_data( $result->body );
			$feed->file          = null;
			$feed->feed_url      = null;
			$feed->permanent_url = null;
			$feed->multifeed_url = [];
		}
	}

}
