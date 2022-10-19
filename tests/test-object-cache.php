<?php
namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;
use PMC\Unit_Test\Mock\Mocker;

/**
 * @coversDefaultClass \PMC\Unit_Test\Object_Cache
 */
class Test_Object_Cache extends Base {
	public function test_cache() {
		wp_cache_set( 'key', 'value', 'group' );
		$this->assertEquals( 'value', wp_cache_get( 'key', 'group' ) );
		wp_cache_delete( 'key', 'group' );
		$this->assertEmpty( wp_cache_get( 'key', 'group' ) );
		$bufs = print_r( $GLOBALS['wp_object_cache']->logs, true );
		$this->assertStringContainsString( 'set: key=key, group=group', $bufs );
		$this->assertStringContainsString( 'delete: key=key, group=group', $bufs );
		wp_cache_flush();
		$this->assertEmpty( $GLOBALS['wp_object_cache']->logs );
	}
}
