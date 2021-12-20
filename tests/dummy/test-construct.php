<?php

namespace PMC\Unit_Test\Tests\Dummy;

use PMC\Global_Functions\Traits\Singleton;

/**
 * @codeCoverageIgnore
 */
class Test_Construct {
	use Singleton;

	public function __construct() {
		add_filter( 'filter', [ $this, 'test' ] );
		add_filter( 'filter1', [ $this, 'test' ] );
		add_filter( 'filter1', [ $this, 'test100' ], 100 );
		add_filter( 'filter1', [ $this, 'test5' ], 5 );
		add_filter( 'filter1', 'function10' );
		add_filter( 'filter1', 'function11', 11 );
		add_filter( 'filter1', [ $this, 'callable' ] );
		add_action( 'action', [ $this, 'action' ] );

		remove_filter( 'filter1', [ $this, 'test10' ] );
		remove_filter( 'filter1', [ $this, 'test20' ], 20 );
		remove_filter( 'filter1', 'function' );
		remove_filter( 'filter1', 'function12', 12 );

		remove_all_filters( 'filter2' );

		add_shortcode( 'shortcode', [ $this, 'test' ] );
		add_shortcode( 'shortcode1', 'test' );
		remove_shortcode( 'shortcode2' );
		remove_shortcode( 'shortcode4' );

		add_shortcode( 'shortcode3', [ $this, 'test' ] );
		remove_all_filters( 'remove_all_filters' );
		add_filter( 'has_filter', '__return_true' );

		remove_filter( 'filter_not', [ $this, 'filter_not' ] );

		remove_shortcode( 'remove_shortcode' );

	}

	public function callable() {
	}

	public function run_custom_method() {
		add_filter( 'run_custom_method', [ $this, 'run_custom_method' ] );
	}

} //end class

//EOF
