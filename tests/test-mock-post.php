<?php
/**
 * Test mock post for unit test.
 *
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Tests;

use PMC\Unit_Test\Utility;

/**
 * Class Test_Mock_Post.
 */
class Test_Mock_Post extends Base {

	public function test_mock() {

		$this->assertEquals( 'post', $this->mock->post->provide_service() );
		$this->assertEquals( 0, Utility::get_hidden_property( $this->mock->post, '_mocked_post_id' ) );

		$post = $this->mock->post( [ 'post_title' => 'unit test' ])->get();
		$this->assertNotEmpty( $post );
		$this->assertEquals( 'unit test', $post->post_title );
		$this->assertEquals( $post, get_post() );
		$this->assertTrue( is_single() );

		// This should not generate a new post
		$post1 = $this->mock->post()->get();
		$post2 = $this->mock->post()->get();
		$this->assertEquals( $post1, $post2 );

		// This should generate a new post
		$post2 = $this->mock->post([])->get();
		$this->assertNotEquals( $post1, $post2 );

		// Test mock with permalink structure & various post related data
		$permalink_structure = $GLOBALS['wp_rewrite']->permalink_structure;
		$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
		$post = $this->mock->post( [
			'taxonomy' => [
				'category' => 'category1',
			],
			'featured_image' => __DIR__ . '/mocks/images/test.jpg',
			'post_meta' => [
				'metakey' => 'metavalue',
			],
			'callback' => function( $post ) {
				wp_update_post( [
					'ID'         => $post->ID,
					'post_title' => 'callback title',
				] );
			},
		] )->get();

		$this->assertEquals( sprintf('/%s/', $post->post_name ), $GLOBALS['wp']->request );

		$GLOBALS['wp_rewrite']->set_permalink_structure( $permalink_structure );

		$this->assertEquals( 'callback title', $post->post_title );
		$this->assertEquals( 'metavalue', get_post_meta( $post->ID, 'metakey', true ) );
		$this->assertTrue( has_category( 'category1', $post ) );
		$this->assertNotEmpty( get_post_thumbnail_id( $post ) );

		// Test is_amp endpoint
		$this->mock->post()->is_amp( true );
		$this->assertTrue( is_amp_endpoint() );
		$this->mock->post()->is_amp( false );
		$this->assertFalse( is_amp_endpoint() );

		$post = $this->mock->post( [ 'post_title' => 'attachment', 'post_type' => 'attachment' ])->get();
		$this->assertTrue( is_single() );
		$this->assertTrue( is_attachment() );
		$this->assertEquals( $post, get_post() );

		$post = $this->mock->post( [ 'post_title' => 'page', 'post_type' => 'page' ])->get();
		$this->assertTrue( is_page() );
		$this->assertEquals( $post, get_post() );

		Utility::set_and_get_hidden_property( $this->mock->post, '_mocked_post_id', 0 );
		$post = $this->mock->post->get();
		$this->assertNotEmpty( $post );
		$this->assertTrue( is_single() );
		$this->assertEquals( $post, get_post() );
		$this->assertNotEquals( 'unit test', $post->post_title );

		Utility::set_and_get_hidden_property( $this->mock, '_test_factory', false );
		Utility::set_and_get_hidden_property( $this->mock, '_test_object', false );
		$new_post = $this->mock->post()->get();
		$this->assertNotEmpty( $new_post );
		$this->assertTrue( is_single() );
		$this->assertEquals( $new_post, get_post() );
		$this->assertNotEquals( 'unit test', $new_post->post_title );
		$this->assertEquals( $post, $new_post );

		Utility::set_and_get_hidden_property( $this->mock->post, '_mocked_post_id', 0 );
		Utility::assert_error( \Error::class , function() {
			$post = $this->mock->post->get();
		} );

		// @TODO: We need to remove the test object to test the generator outside of unit test class
		$posts = $this->mock->set_test_object( $this );

		// Let's do the minimum so we don't waste resources
		$posts = $this->mock->post->seed( 2 )->get_seeds();
		$this->assertEquals( 2, count( $posts ) );

		$mocker = $this->mock->post();
		$mocker->reset();
		$this->assertEquals( 0, Utility::get_hidden_property( $mocker, '_mocked_post_id' ) );
		$this->assertEquals( [], Utility::get_hidden_property( $mocker, '_seeded_posts' ) );
		$this->assertFalse( Utility::get_hidden_property( $mocker, '_is_seeding' ) );

		$posts = $mocker->get_seeds();
		$this->assertEquals( 5, count( $posts ) );

		if ( class_exists( \PMC\Post_Options\Taxonomy::class, false ) ) {
			$instance = \PMC\Post_Options\API::get_instance();
			$instance->register_global_options(
				[
					'test' => [ 'label' => 'post option test' ]
				]
			);
			$post = $this->mock->post( [
				'post_options' => [ 'test' ],
			] )->get();
			$this->assertTrue( $instance->post( $post )->has_option( 'test' ) );
		}

		$this->mock->post()->is_feed( true );
		$this->assertTrue( is_feed() );
		$this->mock->post()->is_feed( false );
		$this->assertFalse( is_feed() );

		$Detected = false;
		try {
			$this->mock->post()->not_found();
		}
		catch( \Error $error ) {
			$Detected = true;
		}
		$this->assertTrue( $Detected, 'Error detecting exception' );

		$mocker = $this->mock->post();
		$wp_query_saved = $GLOBALS['wp_query'];
		unset( $GLOBALS['wp_query'] );
		$Detected = false;
		try {
			$mocker->is_feed();
		}
		catch( \Error $error ) {
			$Detected = true;
		}
		$this->assertTrue( $Detected, 'Error detecting exception' );
		$GLOBALS['wp_query'] = $wp_query_saved;

		$revision = $this->mock->post()->revision();
		$this->assertEquals( 'revision', $revision->post_type );

		$post = $this->mock->post( [ 'post_type' => 'test' ] )->get();
		$this->assertEquals( 'test', get_post_type( $post ) );

	}

}
