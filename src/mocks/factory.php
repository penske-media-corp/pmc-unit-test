<?php
namespace PMC\Unit_Test\Mocks;

use PMC\Global_Functions\Traits\Singleton;

final class Factory {
	use Singleton;

	protected $_registered_mocks = [];
	protected $_test_object      = null;
	protected $_test_factory     = null;

	/**
	 * Bind all mocker object to the Unit Test framework object;
	 * This will allow the mocker object access to the test object reference.
	 * @param $test_object
	 * @return Factory
	 */
	public function set_test_object( object $test_object ) : self {
		$this->_test_object  = $test_object;
		$this->_test_factory = $test_object->factory;
		return $this;
	}

	/**
	 * Magic function to overload and execute mocker class
	 * eg. $this->mock->device( 'ipad' ), $this->mock->device->set( 'ipad' );
	 * @param  string $name     The method name being call
	 * @param  array $arguments The array of arguments
	 * @return mixed
	 */
	public function __call( $name, array $arguments ) {

		if ( isset( $this->_registered_mocks[ $name ] ) ) {
			if ( is_callable( $this->_registered_mocks[ $name ] ) ) {
				return call_user_func_array( $this->_registered_mocks[ $name ], $arguments );
			}
			if ( is_callable( [ $this->_registered_mocks[ $name ], 'mock' ] ) ) {
				return call_user_func_array( [ $this->_registered_mocks[ $name ], 'mock' ], $arguments );
			}
		}

		throw new \Error( sprintf( 'Call to un-registered mocker "%s"', $name ) );

	}

	/**
	 * Magic function to return the the mocking data object for the request service
	 * @param $name
	 * @return mixed|Factory
	 */
	public function __get( $name ) {
		return isset( $this->_registered_mocks[ $name ] ) ? $this->_registered_mocks[ $name ] : $this;
	}

	/**
	 * Return the unit test frame work object
	 * @return null | object
	 */
	public function test_object() {
		return $this->_test_object;
	}

	/**
	 * Return the unit test factory to generate the wp related content type
	 * @return null | object
	 */
	public function test_factory() {
		return $this->_test_factory;
	}

	/**
	 * Register the data mocking service
	 * @param $mocker        The callable mocker object
	 * @param bool $service  Optional service name, if left empty; We will auto determine by calling mocker class function provide_service
	 * @return Factory
	 */
	public function register( $mocker, $service = false ) : self {

		if ( is_callable( $mocker ) ) {
			if ( $service ) {
				$this->_registered_mocks[ $service ] = $mocker;
			} else {
				throw new \Error( sprintf( 'Error registering callable mock, service name required' ) );
			}
		} else {
			if ( is_string( $mocker ) && class_exists( $mocker ) ) {
				$mocker = new $mocker();
			}

			if ( is_object( $mocker ) ) {
				if ( ! $service ) {
					if ( is_callable( [ $mocker, 'provide_service' ] ) ) {
						$service = $mocker->provide_service();
					}
				}
				if ( $service ) {
					$this->_registered_mocks[ $service ] = $mocker;
				} else {
					throw new \Error( sprintf( 'Error registering mocker with unknown type: %s', is_string( $mocker ) ? $mocker : print_r( $mocker, true ) ) ); // phpcs:ignore
				}

			} else {
				throw new \Error( sprintf( 'Error registering mocker: %s', is_string( $mocker ) ? $mocker : print_r( $mocker, true ) ) ); // phpcs:ignore
			}

		}

		return $this;

	}

	/**
	 * Trigger all registered mocker services to reset and clean out all mocked data
	 */
	public function reset() : self {
		foreach ( $this->_registered_mocks as $mocker ) {
			if ( method_exists( $mocker, 'reset' ) ) {
				$mocker->reset();
			}
		}
		return $this;
	}

	/**
	 * Trigger all registered mocker to initialize
	 */
	public function init() : self {
		foreach ( $this->_registered_mocks as $mocker ) {
			if ( method_exists( $mocker, 'init' ) ) {
				$mocker->init();
			}
		}
		return $this;
	}

}
