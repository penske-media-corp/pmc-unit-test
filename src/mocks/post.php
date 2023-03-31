<?php
namespace PMC\Unit_Test\Mocks;

use PMC\Unit_Test\Interfaces\Mocker as Mocker_Interface;
use PMC\Unit_Test\Interfaces\Seeder as Seeder_Interface;
use PMC\Unit_Test\Traits\Mocker as Mocker_Trait;
use PMC\Unit_Test\Mocks\Factory;

/**
 * Note: Do not add return type any of the member function since they might be override by sub-class
 * The return type override will not be supported until PHP 7.4
 *
 * The basic Post type data mocker; All data mocker that use WP Post must extends this class.
 *
 * Class Post
 *
 * @package PMC\Unit_Test\Mocks
 */
class Post
	implements Mocker_Interface, Seeder_Interface {
	use Mocker_Trait;

	protected $_mocked_post_id = 0;
	protected $_seeded_posts   = [];
	protected $_is_seeding     = false;
	protected $_ids            = [];

	/**
	 * Provide post mocking service
	 *
	 * @return string
	 */
	public function provide_service() {
		return 'post';
	}

	/**
	 * Clean up all mocked data
	 */
	public function reset() {

		foreach ( $this->_ids as $id ) {
			wp_delete_post( $id );
		}

		$this->_ids            = [];
		$this->_seeded_posts   = [];
		$this->_mocked_post_id = 0;
		$this->_is_seeding     = false;

	}

	/**
	 * Auto generate and mock the current post
	 *
	 * @param Array $args
	 * @return $this
	 */
	public function mock( array $args = [] ) {

		// Prevent original $args reference from being modified.
		$args = array_merge( [], $args );

		if ( 0 !== func_num_args() || empty( $this->_mocked_post_id ) ) {

			// Make sure the post type exists before we try to mock it
			if ( ! empty( $args['post_type'] ) && ! post_type_exists( $args['post_type'] ) ) {
				register_post_type( $args['post_type'] );
			}

			$post                  = $this->generate( $args );
			$this->_mocked_post_id = $post->ID;
			$this->_ids[]          = $post->ID;

			if ( isset( $args['taxonomy'] ) ) {
				$post_type = get_post_type( $post );
				foreach ( $args['taxonomy'] as $taxonomy => $terms ) {
					register_taxonomy_for_object_type( $taxonomy, $post_type );
					wp_set_object_terms( $post->ID, $terms, $taxonomy );
				}
				unset( $args['taxonomy'] );
			}

			if ( isset( $args['post_meta'] ) ) {
				foreach ( $args['post_meta'] as $key => $value ) {
					add_post_meta( $post->ID, $key, $value );
				}
				unset( $args['post_meta'] );
			}

			do_action( 'pmc_mocked_post', $post, $args );

			if ( isset( $args['callback'] ) && is_callable( $args['callback'] ) ) {
				call_user_func( $args['callback'], $post );
				unset( $args['callback'] );
			}
		}

		if ( ! empty( $this->_mocked_post_id ) && ! $this->_is_seeding ) {
			$test_object = Factory::get_instance()->test_object();
			if ( $test_object ) {
				$test_object->go_to( $this->_mocked_post_id );
			} else {
				query_posts( 'p=' . $this->_mocked_post_id );
				$GLOBALS['post'] = get_post( $this->_mocked_post_id );
				$GLOBALS['wp_query']->reset_postdata();
				$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
			}
		}

		return $this;

	}

	public function revision() {
		$function = function() {
			return 1;
		};
		add_filter( 'wp_revisions_to_keep', $function );
		$revision_id = wp_save_post_revision( $this->get() );
		remove_filter( 'wp_revisions_to_keep', $function );
		$this->_ids[] = $revision_id;
		return get_post( $revision_id );
	}

	/**
	 * Return the current mocked post
	 *
	 * @return \WP_Post
	 */
	public function get() {
		if ( ! $this->_mocked_post_id ) {
			$this->mock();
		}
		return get_post( $this->_mocked_post_id );
	}

	/**
	 * Generate multiple mocked post with the provided $args values for each post
	 *
	 * @param int   $count
	 * @param array $args
	 * @return $this
	 */
	public function seed( int $count = 5, array $args = [] ) {
		$this->_is_seeding = true;
		$args              = array_merge(
			[
				'post_status'  => 'publish',
				'post_title'   => 'Post #%d',
				'post_excerpt' => 'Excerpt #%d',
				'post_content' => 'Content #%d',
			],
			$args
		);

		$should_dates_be_spread_out = ( ! empty( $args['post_date'] ) && 'spread-out' === strtolower( $args['post_date'] ) );
		$fixed_time                 = time();

		for ( $i = 0; $i < $count; $i++ ) {

			$spread_out_time   = $fixed_time - intval( $i * DAY_IN_SECONDS );
			$args['post_date'] = ( true === $should_dates_be_spread_out ) ? $spread_out_time : $fixed_time;
			$args['post_date'] = date( 'Y-m-d H:i:s', $args['post_date'] );  // phpcs:ignore

			$post                  = $this->mock( $args )->get();
			$this->_ids[]          = $post->ID;
			$this->_seeded_posts[] = $post;

		}

		$this->_is_seeding = false;

		return $this; // Return $this to allow chained statements
	}

	/**
	 * Return the current list of seeded posts
	 *
	 * @return array
	 */
	public function get_seeds() {
		if ( empty( $this->_seeded_posts ) ) {
			$this->seed();
		}
		return $this->_seeded_posts;
	}

	/**
	 * Mock the current post as amp endpoint
	 *
	 * @param bool $is_amp_endpoint
	 * @return $this
	 */
	public function is_amp( $is_amp_endpoint = true ) {

		// AMP endpoint must have a default global post
		$this->mock();

		if ( ! defined( 'AMP_QUERY_VAR' ) ) {
			// AMP plugin should already have this variable define, fallback condition never reached by unit test
			define( 'AMP_QUERY_VAR', apply_filters( 'amp_query_var', 'amp' ) ); // @codeCoverageIgnore
		}

		// AMP endpoint can't be in admin screen
		if ( $is_amp_endpoint ) {
			set_current_screen( 'front' );
		}

		global $wp_query;
		if ( ! empty( $wp_query ) ) {
			$wp_query->set( AMP_QUERY_VAR, $is_amp_endpoint );
		}

		return $this;
	}

	/**
	 * Magic function to add support to set wp_query->is_[name] property
	 *
	 * @param $name
	 * @param array $arguments
	 */
	public function __call( $name, array $arguments ) {
		global $wp_query;
		if ( empty( $wp_query ) ) {
			throw new \Error( spritnf( 'WP Post has not been mocked' ) );
		}
		if ( substr( $name, 0, 3 ) === 'is_' ) {
			if ( isset( $wp_query->$name ) ) {
				$wp_query->$name = isset( $arguments[0] ) ? (bool) $arguments[0] : true;
			}
			return $this;
		}
		throw new \Error( sprintf( 'Call to unknown function "%s"', $name ) );
	}

}
