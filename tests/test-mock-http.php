<?php
namespace PMC\Unit_Test\Tests;
use PMC\Unit_Test\Utility;

// All test extends the base test abstract class
class Mock_Requests extends Base {

	public function test_mock_requests() {

		$this->assertEquals('http', $this->mock->http->provide_service() );

		$this->mock->http()
			->enable()
			->default_not_found( false, false );

		$this->mock->http( 'https://ifconfig.me/ip', '[mock result]' );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' );
		$this->assertTrue( isset( $mocks['https://ifconfig.me/ip'] ) );

		$result = \Requests::get( 'https://ifconfig.me/ip');
		$this->assertTrue( $result->success );
		$this->assertEquals( '[mock result]', $result->body );

		$this->mock->http( 'https://ifconfig.me/ip', [
				'headers' => [
						'HTTP/1.1 201 OK',
						'Set-Cookie: name=value',
						'Set-Cookie: name2=value2',
						'Head: Header1',
						'Head: Header2',
					],
				'body' => 'body',
			] );

		$result = \Requests::get( 'https://ifconfig.me/ip');

		$this->assertTrue( $result->success );
		$this->assertEquals( 'body', $result->body );
		$this->assertEquals( '201', $result->status_code );
		$this->assertEquals( 'value', $result->cookies['name']->value );

		$this->mock->http()->remove( 'https://ifconfig.me/ip' );

		try {
			$result = \Requests::get('https://ifconfig.me/ip');

			// Condition test, in case where ifconfig.co service isn't responding
			if ($result->success) {
				$this->assertNotEquals('body', $result->body);
			}
		}
		catch(\Requests_Exception $ex) {
		}


		$this->mock->http( 'https://ifconfig.me/ip', [
				'headers' => [
						'HTTP/1.1 200 OK',
						'Set-Cookie: name=value;',
					],
				'body' => function( &$response_headers, $url, $headers ) {
					$response_headers = (array) $response_headers;
					$response_headers[] = 'function: header';
					return 'function body';
				},
			] );

		$result = \Requests::get( 'https://ifconfig.me/ip');
		$this->assertTrue( $result->success );
		$this->assertEquals( 'function body', $result->body );
		$this->assertEquals( 'header', $result->headers['function']);

		$this->mock->http( 'https://ifconfig.me/ip', [
				'body' => [ 'json' => 'result'],
			] );

		$result = \Requests::get( 'https://ifconfig.me/ip' );
		$this->assertTrue( $result->success );
		$this->assertEquals( '{"json":"result"}', $result->body );

		$this->mock->http( 'https://ifconfig.me/ip', [
				'file' => __DIR__ . '/mocks/mock-test/default.json',
			] );

		$result = \Requests::get( 'https://ifconfig.me/ip' );
		$this->assertTrue( $result->success );
		$this->assertEquals( file_get_contents( __DIR__ . '/mocks/mock-test/default.json' ), $result->body );

		$this->mock->http( 'https://ifconfig.me/ip', [
				'raw' => __DIR__ . '/mocks/mock-test/http-raw.txt',
			] );
		$result = \Requests::get( 'https://ifconfig.me/ip' );
		$this->assertTrue( $result->success );
		$this->assertEquals( 'This is a raw file http response mock', $result->body );

		$this->mock->http( 'https://ifconfig.me/ip', [
				'body' => function( &$response_headers, $url, $headers, $data, $options ) {
					return [ 'data' => $data, 'headers' => $headers ];
				},
			] );
		$result = \Requests::post( 'https://ifconfig.me/ip', [ 'header' => 'value' ], [ 'name' => 'pair' ] );
		$this->assertTrue( $result->success );
		$this->assertEquals( '{"data":{"name":"pair"},"headers":{"header":"value"}}', $result->body );

		$this->mock->http( 'https://ifconfig.me/ip', [
				'body' => function() {
					return '__remote_get';
				},
			] );

		try {
			$result = \Requests::get( 'https://ifconfig.me/ip' );
			$this->assertTrue( $result->success );
			$this->assertNotEmpty( $result->body );
			$this->assertRegExp( '/\d+\.\d+\.\d+\.\d+/', $result->body );
		}
		catch(\Requests_Exception $ex) {
		}

		$this->mock->http( '*', 'intercept all traffics' );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' );
		$this->assertTrue( isset( $mocks['*'] ) );

		try {
			$result = \Requests::get('https://ifconfig.me/ip');
			$this->assertTrue($result->success);
			$this->assertNotEmpty($result->body);
			$this->assertRegExp('/\d+\.\d+\.\d+\.\d+/', $result->body);
		}
		catch(\Requests_Exception $ex) {
		}

		$result = \Requests::get( 'https://ifconfig.me/test' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'intercept all traffics', $result->body );

		$this->mock->http->once( 'https://ifconfig.me/once', 'once' );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' );
		$this->assertTrue( isset( $mocks['https://ifconfig.me/once'] ) );
		$result = \Requests::get( 'https://ifconfig.me/once' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'once', $result->body );

		$result = \Requests::get( 'https://ifconfig.me/once' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'intercept all traffics', $result->body );

		$this->mock->http->once( 'https://ifconfig.me/next', 'once' );
		$this->mock->http->next( 'https://ifconfig.me/next', 'next' );

		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' );
		$this->assertTrue( isset( $mocks['https://ifconfig.me/next'] ) );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_next_match' );
		$this->assertTrue( isset( $mocks['https://ifconfig.me/next'] ) );

		$result = \Requests::get( 'https://ifconfig.me/next' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'next', $result->body );

		$result = \Requests::get( 'https://ifconfig.me/next' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'once', $result->body );

		$this->mock->http->next( '*', 'next' );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_next_queues' );
		$this->assertTrue( 1 === count( $mocks )  );
		$result = \Requests::get( 'https://ifconfig.me/test' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'next', $result->body );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_next_queues' );
		$this->assertTrue( 0 === count( $mocks )  );

		$this->mock->http->once( '*', [ 'raw' => "HTTP/1.1 200 OK\r\n\r\nonce" ] );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' );
		$this->assertTrue( isset( $mocks['*'] ) );
		$result = \Requests::get( 'https://ifconfig.me/test' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'once', $result->body );
		$mocks = Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' );
		$this->assertFalse( isset( $mocks['*'] ) );

		$this->mock->http( '*', [ 'raw' => "HTTP/1.1 200 OK\r\n\r*" ] );
		$this->assertEquals( [ 'raw' => "HTTP/1.1 200 OK\r\n\r*" ], Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' )['*'] );

		$this->mock->http( '*', [ 'raw' => "HTTP/1.1 200 OK\r\n\r\$this->mock->http" ] );
		$this->assertEquals( [ 'raw' => "HTTP/1.1 200 OK\r\n\r\$this->mock->http" ], Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' )['*'] );

		$this->mock->http->dispose();
		$this->assertEmpty( Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_transports_stored' ) );
		$this->assertEmpty( Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_match' ) );
		$this->assertEmpty( Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_next_match' ) );
		$this->assertEmpty( Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_next_queues' ) );

		try {
			$result = \Requests::get('https://ifconfig.me/ip');
			$this->assertTrue($result->success);
			$this->assertNotEmpty($result->body);
			$this->assertRegExp('/\d+\.\d+\.\d+\.\d+/', $result->body);
		}
		catch(\Requests_Exception $ex) {
		}


		$this->mock->http->next( '*', 'test' );
		$result = \Requests::get( 'https://ifconfig.me/ip' );
		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'test', $result->body );
		$this->assertEmpty( Utility::get_hidden_static_property( \PMC\Unit_Test\Mocks\Http::class, '_mock_next_queues' ) );

		$this->mock->http->intercept_transport();
		$this->mock->http()->reset()
			->default_not_found( true, true );

		$result = \Requests::get( 'https://ifconfig.me/ip' );

		$this->assertFalse( $result->success );
		$this->assertEquals( 404, $result->status_code );
		$this->assertEquals( 'Request not mocked: https://ifconfig.me/ip', $result->body );

		$this->mock->http()
			->disable()
			->reset();

	}

	public function test_fetch_feed() {
		$this->mock->http(
			'http://localhost/xml',
			'<?xml version="1.0"?>
			<rss version="2.0">
				<channel>
				<title>Channel Title</title>
				<item>
					<title>Item Title</title>
					<link>https://localhost/item</link>
				</item>
				</channel>
			</rss>'
		);
		$feed = fetch_feed( 'http://localhost/xml' );
		$bufs = print_r( $feed->get_items(), true );
		$this->assertStringContainsString( 'Item Title', $bufs );
		$this->assertStringContainsString( 'Channel Title', $bufs );
		$this->assertStringContainsString( 'https://localhost/item', $bufs );
	}

}
