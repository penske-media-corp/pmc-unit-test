<?php
// phpcs:disable WordPress.WP.GlobalVariablesOverride

namespace PMC\Unit_Test\Mocks;

use PMC\Unit_Test\Interfaces\Mocker as MockerInterface;
use PMC\Unit_Test\Mocks\Factory;
use PMC\Unit_Test\Utility;

/**
 * Mocker for mocking wp query
 *
 * Class Input
 * @package PMC\Unit_Test\Mocks
 */
final class Wp
	implements MockerInterface {

	protected $_backup_wp_query     = null;
	protected $_backup_wp_the_query = null;

	public function __construct() {
		$this->mock = Factory::get_instance();
	}

	public function provide_service() : string {
		return 'wp';
	}

	/**
	 * @param array $args
	 *    [
	 *          // To mock custom archive page with array query
	 *          'query' => [ ... ],  // array used by WP_Query
	 *
	 *          // To mock custom archive page with instance of WP_Query
	 *          'wp_query' => instance of WP_Query
	 *
	 *          'is_*' => true|false,
	 *          'query_vars' => [
	 *                 'name' => 'value',
	 *                 'name2' => 'value2',
	 *                  ...
	 *              ],
	 *          'request'               => <string>,
	 *          'comment_count'         => <number>,
	 *          'max_num_pages'         => <number>,
	 *          'max_num_comment_pages' => <number>,
	 *    ]
	 * @return $this
	 */
	public function mock( $args = [] ) : self {
		if ( empty( $this->_backup_wp_query ) ) {
			$this->_backup_wp_query = Utility::clone_object( $GLOBALS['wp_query'] );
		}
		if ( empty( $this->_backup_wp_the_query ) ) {
			$this->_backup_wp_the_query = Utility::clone_object( $GLOBALS['wp_the_query'] );
		}

		if ( ! empty( $GLOBALS['wp_the_query'] ) ) {
			$GLOBALS['wp_the_query'] = Utility::clone_object( $GLOBALS['wp_the_query'] );
		}

		if ( ! empty( $args['query'] ) ) {
			$GLOBALS['wp_query'] = new \WP_Query( $args['query'] );
			unset( $args['query'] );
		} elseif ( ! empty( $args['wp_query'] ) && $args['wp_query'] instanceof \WP_Query ) {
			$GLOBALS['wp_query'] = $args['wp_query'];
			unset( $args['wp_query'] );
		}

		$allow_properties = [
			'query_vars',
			'request',
			'comment_count',
			'max_num_pages',
			'max_num_comment_pages',
			'queried_object_id',
			'queried_object',
		];

		if ( ! empty( $args['feed'] ) ) {
			$args['query_vars']['feed'] = $args['feed'];
			$args['is_feed']            = true;
			unset( $args['feed'] );
		}

		if ( ! empty( $args['queried_object'] ) && is_object( $args['queried_object'] ) ) {
			if ( isset( $args['queried_object']->term_id ) ) {
				$args['queried_object_id'] = $args['queried_object']->term_id;
			} elseif ( isset( $args['queried_object']->ID ) ) {
				$args['queried_object_id'] = $args['queried_object']->ID;
			}
		}

		foreach ( $args as $key => $value ) {
			if ( in_array( $key, (array) $allow_properties, true ) || 'is_' === substr( $key, 0, 3 ) ) {
				switch ( $key ) {
					case 'is_single':
					case 'is_singular':
					case 'is_attachment':
						if ( ! empty( $value ) ) {
							\PMC\Unit_Test\Utility::deprecated( $key, '$this->mock->post( [ ... ] )' );
						}
						break;
					case 'is_front_page':
						$key = 'is_home';
						update_option( 'show_on_front', $value ? 'posts' : false );
						break;
				}

				if ( ! empty( $value )
					&& (
						in_array( $key, [ 'queried_object_id', 'queried_object' ], true )
						|| 'is_' === substr( $key, 0, 3 )
					)
				) {
					$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
				}

				if ( 'is_home' === $key && true === $value ) {
					$test_object = Factory::get_instance()->test_object();
					if ( $test_object ) {
						$test_object->go_to( '/' );
					}
					$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
				}

				$GLOBALS['wp_query']->$key = $value; // phpcs:ignore
			} else {
				switch ( $key ) {
					case 'post':
						throw new \Error( sprintf( 'mocking $wp_query->%s is not supported, please use $this->mock->post( [ ... ] )', $key ) );
				}
				throw new \Error( sprintf( 'mocking $wp_query->%s is not supported', $key ) );
			}
		}
		return $this;
	}

	public function reset() {
		if ( ! empty( $this->_backup_wp_query ) ) {
			$GLOBALS['wp_query']    = $this->_backup_wp_query;
			$this->_backup_wp_query = null;
		}
		if ( ! empty( $this->_backup_wp_the_query ) ) {
			$GLOBALS['wp_the_query']    = $this->_backup_wp_the_query;
			$this->_backup_wp_the_query = null;
		}
	}

	public function set( $name, $value ) : self {
		$GLOBALS['wp_query']->set( $name, $value );
		return $this;
	}

	public function set_404() : self {
		$GLOBALS['wp_query']->set_404();
		return $this;
	}

}
