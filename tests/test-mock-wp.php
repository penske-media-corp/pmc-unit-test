<?php
/**
 * Test Mock WP for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;

/**
 * Class Test_Mock_Wp.
 */
class Test_Mock_Wp extends Base {

	public function test_mock() {

		$mocker = new \PMC\Unit_Test\Mocks\Wp();
		$this->assertEquals( 'wp', $mocker->provide_service() );

		$this->mock->wp([
			'is_archive' => false,
			'is_paged'   => false,
			'is_home'    => false,
			'is_single'  => false,
		] );

		$this->assertFalse( is_archive() );
		$this->assertFalse( is_paged() );
		$this->assertFalse( is_home() );
		$this->assertFalse( is_single() );

		$this->mock->wp([
			'is_home'    => true,
			'is_archive' => true,
			'is_paged'   => true,
			'is_single'  => true,
		] );

		$this->assertTrue( is_home() );
		$this->assertTrue( is_archive() );
		$this->assertTrue( is_paged() );
		$this->assertTrue( is_single() );

		$this->mock->wp()->set_404()->set('test', 'passed');
		$this->assertTrue( is_404() );
		$this->assert_array_contains( [ 'test' => 'passed' ], $GLOBALS['wp_query']->query_vars );


		$this->mock->post([]);
		$query = $GLOBALS['wp_query']->query;
		$this->mock->wp()->reset();
		$this->mock->wp( [
			'query'    => $query,
			'is_paged' => true,
			] );
		$this->assertEquals( $query, $GLOBALS['wp_query']->query );
		$this->assertTrue( is_paged() );

		$post = $this->mock->post([]);
		$this->mock->wp( [
				'wp_query'  => new \WP_Query( [ 'post_type' => 'post' ] ),
				'is_archive' => true,
			] );
		$this->assertGreaterThan( 1, count( $GLOBALS['wp_query']->posts ) );
		$this->assertGreaterThan( 1, $GLOBALS['wp_query']->found_posts );

		Utility::assert_error( \Error::class, function() {
			$this->mock->wp( [ 'name' => 'value' ] );
		} );

		Utility::assert_error( \Error::class, function() use( $post ) {
			$this->mock->wp( [ 'post' => $post ] );
		} );

		$wp_query = $GLOBALS['wp_query'];
		$this->mock->wp()->reset();
		$this->assertNotEquals( $wp_query, $GLOBALS['wp_query'] );
		$this->assertTrue( $GLOBALS['wp_query'] instanceof \WP_Query );

		$this->mock->wp( [ 'is_home' => false, 'is_front_page' => true ] );
		$this->assertTrue( is_home() );
		$this->assertTrue( is_front_page() );

		$this->mock->wp( [ 'is_front_page' => false, 'is_home' => true ] );
		$this->assertFalse( is_front_page() );
		$this->assertTrue( is_home() );

		$this->mock->wp( [ 'is_home' => false ] );
		$this->mock->wp( [ 'is_archive' => true ] );
		$this->assertFalse( is_home() );
		$this->assertTrue( is_main_query() );
		$this->assertTrue( is_archive() );

		$mock = $this->mock->wp( [ 'is_home' => true ] );
		$this->assertTrue( is_main_query() );
		$backup_the_query = Utility::get_hidden_property( $mock, '_backup_wp_the_query' );
		$this->assertNotEmpty( $backup_the_query );

		$this->mock->wp( [ 'is_main' => false ] );
		$this->assertFalse( is_main_query() );

		$this->mock->wp( [ 'feed' => 'test' ] );
		$this->assertTrue( is_feed() );
		$this->assertTrue( is_feed( 'test' ) );
		$this->assertFalse( is_feed( 'false' ) );
	}

	public function test_mock_queried_object() {
		$object = new \WP_Term( (object)[ 'term_id' => 1, 'name' => 'test', 'slug' => 'test', 'taxonomy' => 'test' ] );
		$this->mock->wp( [
			'queried_object' => $object,
		] );

		$this->assertSame( $object, get_queried_object() );
		$this->assertEquals( $object->term_id, get_queried_object_id() );

		$object = new \WP_Post( (object)[ 'ID' => 2 ] );
		$this->mock->wp( [
			'queried_object' => $object,
		] );
		$this->assertSame( $object, get_queried_object() );
		$this->assertEquals( $object->ID, get_queried_object_id() );

	}

}
