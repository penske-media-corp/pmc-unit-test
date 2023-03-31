<?php
/**
 * Singleton trait which implements Singleton pattern in any class in which this trait is used.
 *
 * Dependent items can use the `pmc_singleton_init_{$called_class}` hook to
 * execute code immediately after _init() is called.
 *
 * Using the singleton pattern in WordPress is an easy way to protect against
 * mistakes caused by creating multiple objects or multiple initialization
 * of classes which need to be initialized only once.
 *
 * With complex plugins, there are many cases where multiple copies of
 * the plugin would load, and action hooks would load (and trigger) multiple
 * times.
 *
 * If you're planning on using a global variable, then you should implement
 * this trait. Singletons are a way to safely use globals; they let you
 * access and set the global from anywhere, without risk of collision.
 *
 * If any method in a class needs to be aware of "state", then you should
 * implement this trait in that class.
 *
 * If any method in the class need to "talk" to another or be aware of what
 * another method has done, then you should implement this trait in that class.
 *
 * If you specifically need multiple objects, then use a normal class.
 *
 * If you're unsure, ask in engineering chat.
 *
 * @since 2017-06-19 Amit Gupta
 * @package pmc-unit-test
 */

namespace PMC\Unit_Test\Traits;

/**
 * Trait Singleton.
 */
trait Singleton {

	protected static $_instance = array();

	/**
	 * Protected class constructor to prevent direct object creation
	 *
	 * This is meant to be overridden in the classes which implement
	 * this trait. This is ideal for doing stuff that you only want to
	 * do once, such as hooking into actions and filters, etc.
	 */
	protected function  __construct() { }

	/**
	 * Prevent object cloning
	 */
	final protected function  __clone() { }

	/**
	 * This method returns new or existing Singleton instance
	 * of the class for which it is called. This method is set
	 * as final intentionally, it is not meant to be overridden.
	 *
	 * @return object|static Singleton instance of the class.
	 */
	final public static function get_instance() {

		/*
		 * If this trait is implemented in a class which has multiple
		 * sub-classes then static::$_instance will be overwritten with the most recent
		 * sub-class instance. Thanks to late static binding
		 * we use get_called_class() to grab the called class name, and store
		 * a key=>value pair for each `classname => instance` in self::$_instance
		 * for each sub-class.
		 */
		$called_class = get_called_class();

		if ( ! isset( static::$_instance[ $called_class ] ) ) {

			static::$_instance[ $called_class ] = new $called_class();

			/*
			 * Run _init() method, if it exists in class.
			 * This is only for backwards compatibility with existing code.
			 */
			if ( method_exists( static::$_instance[ $called_class ], '_init' ) ) {
				static::$_instance[ $called_class ]->_init();
			}

			/*
			 * Dependent items can use the `pmc_singleton_init_{$called_class}` hook to execute code
			 * immediately after _init() is called.
			 */
			do_action( sprintf( 'pmc_singleton_init_%s', $called_class ) );

		}

		return static::$_instance[ $called_class ];

	}

}	//end trait

//EOF
