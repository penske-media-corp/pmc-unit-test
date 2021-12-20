# Mock WP HTTP Requests


---
It is best practice to mock HTTP requests in unit tests instead of making the actual call to a remote endpoint and test the data returned.

The class `PMC\Unit_Test\Mock\Requests` was written to intercept the WP Core Request http transport and override its behavior to allow unit testing without actually connecting to the remote server by simulating the actual text content returned. This method work for all plugin that use the WP core Requests class; including the related wp_remote_get helper functions.


### How to use

To intercept and mock a url with some data, extends the unit test base class PMC\Unit_Test\Base.
The default mockers are auto registered via the magic function prefix call within unit test 
class as `$this->mock->http()`, `$this->mock->http()->once()`, `$this->mock->http()->next()`

Once the url has been mocked, any remote request to the matching url will return the mocked data.

The `mock` function take two parameters: `$url` & `$data`
where, `$url` is the full url string for the remote request.
And `$data` can be one of the following format:

- string: the full text string of the response body
- object: the object will be json encoded into string and return as the response body
- callable function ( &$response_headers, $url, $headers, $data, $options ) that return the response body text
- array:
    - headers: can be a strings or array list of headers
    - body: string | object | callable function ( &$headers )
    - file: full path pointing to the full body text file
    - raw: The http raw plaintext of the result or file to the raw data

#### Examples

Mocking for code that use \Requests class directly:

	$this->mock->http( 'https://ifconfig.co/', '127.0.0.1' );
	$result = \Requests::get( 'https://ifconfig.co/' );
	$this->assertEquals( '127.0.0.1', $result->body );

Mocking for code that use one of the related wp_remote_get functions:

	$this->mock->http( 'https://ifconfig.co/', '127.0.0.2' );
	$result = vip_safe_wp_remote_get( 'https://ifconfig.co/' );
	$this->assertEquals( '127.0.0.2', $result['body'] );

Mocking result with full HTTP Headers:

	$this->mock->http( 'https://ifconfig.co/', [
			'headers' => [
					'HTTP/1.1 201 OK',
					'Set-Cookie: name=value;',
				],
			'body' => 'body',
		] );

	$result = \Requests::get( 'https://ifconfig.co/' );
	$this->assertEquals( 'body', $result->body );
	$this->assertEquals( '201', $result->status_code );
	$this->assertEquals( 'value', $result->cookies['name']->value );

Mocking the result with large body text from a file:

	$this->mock->http( 'https://ifconfig.co/', [
			'headers' => [
					'HTTP/1.1 201 OK',
					'Set-Cookie: name=value;',
				],
			'file' => __DIR__ . '/mocks/test-result.json',
		] );

Mocking the result using a function to inspect and inject content to the body and/or capture for validation.  This would be useful to intercept the data to be send to the remote endpoint; for example during post save event where the cdn plugin would send data to remote endpoint to clear CDN cache.

	$this->mock->http( 'https://ifconfig.co/', [
			'body' => function( &$response_headers, $url, $headers, $data, $options ) {
				return [ 'data' => $data, 'headers' => $headers ];
			},
		] );

To inspect the request data but not intercept and let request passthrough:

	$this->mock->http( 'https://ifconfig.co/', [
			'body' => function( &$response_headers, $url, $headers, $data, $options ) {
				// todo: log the $headers & $data

				return '__remote_get'; // magic keyword
			},
		] );

To intercept all traffics:

	$this->mock->http( '*', [
			'body' => function( &$response_headers, $url, $headers, $data, $options ) {
				// do something

				return '...';
			},
		] );

To mock a request only once then auto remove:

	$this->mock->http()->once( 'https://ifconfig.co/', 'once' );

To mock the request for the next call in higher priority and auto remove:

	$this->mock->http()->next( 'https://ifconfig.co/', 'next' );

To mock next call to any request and auto remove:

	$this->mock->http()->next( '*', 'next' );

Example using custom functions to intercept and validate the api calls:

		$client = \PMC\OAuth\Client::register_instance( 'my-oauth-api');
		$client->store_options( [
				'client_id'          => 'client_id',
				'client_secret'      => 'client_secret',
				'scope'              => 'scope',
				'realm'              => 'realm',
				'authorize_endpoint' => 'http://oauth.domain.com/v2/oauth/authorize',
				'token_endpoint'     => 'http://oauth.domain.com/v2/oauth/access_token',
				'api_endpoint'       => 'http://api.domain.com',
			] );

		$this->mock->http->next( '*', [
				'headers' => [
						'HTTP/1.1 200 OK',
					],
				'body' => function( &$response_headers, $url, $headers, $data, $options ) {
					$this->assertEquals( 'https://oauth.domain.com/v2/oauth/access_token', $url );
					$this->assertArraySubset( [
							'client_id'     => 'client_id',
							'client_secret' => 'client_secret',
							'grant_type'    => 'authorization_code',
							'realm'         => 'realm',
							'scope'         => 'scope',
						], $data );
					return '{"access_token":"token","token_type": "Bearer", "expires_in": 3600, "refresh_token": "refresh"}';
				}
			] );

		$_GET['oauth-api'] = $api_name;
		$_GET['code']      = 'code';
		$_GET['state']     = Utility::get_hidden_property( $client, '_state');
		$client->maybe_refresh_token();
		$_GET = [];

		$this->assertEquals( 'token', Utility::get_hidden_property( $client, '_access_token' ) );
		$this->assertEquals( 'refresh', Utility::get_hidden_property( $client, '_refresh_token' ) );


		$this->mock->http( '*', function( &$response_headers, $url, $headers, $data, $options ) use( $client ) {
			$this->assertEquals( 'https://api.domain.com/fail', $url );
			$this->assertArraySubset( [
					'Authorization' => sprintf( '%s %s', Utility::get_hidden_property( $client, '_token_type' ), Utility::get_hidden_property( $client, '_access_token' ) ),
				], $headers );
			$this->assertArraySubset( [
					'p1' => 'v1',
				], $data );
			$response_headers .= "HTTP/1.1 401 Unauthorized\n";
			return '{"result": "test-failed"}';
		} );

		$result = $client->post( '/fail', [ 'p1' => 'v1' ] );
		$result = $client->post( 'fail', [ 'p1' => 'v1' ] );
		$this->assertFalse( $result->success );
		$this->assertEquals( 401, $result->code );
		$this->assertEquals( 'Unauthorized', $result->message );

