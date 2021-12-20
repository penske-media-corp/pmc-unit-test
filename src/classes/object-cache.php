<?php
/**
 * Extends the WP Object Cache to allow unit test to inspect the caching behaviors as needed
 */

namespace PMC\Unit_Test;
use WP_Object_Cache;

class Object_Cache extends WP_Object_Cache {
	public $logs = [];

	public function delete( $key, $group = 'default', $time = 0, $server_key = '', $by_key = false ) {
		$this->logs[] = sprintf( 'delete: key=%s, group=%s', $key, $group );
		return parent::delete( $key, $group, $time, $server_key, $by_key );
	}

	public function get( $key, $group = 'default', $force = false, &$found = null, $server_key = '', $by_key = false, $cache_cb = null, &$cas_token = null ) {
		$this->logs[] = sprintf( 'get: key=%s, group=%s', $key, $group );
		return parent::get( $key, $group, $force, $found, $server_key, $by_key, $cache_cb, $cas_token );
	}

	public function flush( $delay = 0 ) {
		$this->logs = [];
		return parent::flush( $delay );
	}

	public function set( $key, $value, $group = 'default', $expiration = 0, $server_key = '', $by_key = false ) {
		$this->logs[] = sprintf( 'set: key=%s, group=%s', $key, $group );
		return parent::set( $key, $value, $group, $expiration, $server_key, $by_key );
	}

}
