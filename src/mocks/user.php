<?php
namespace PMC\Unit_Test\Mocks;

use PMC\Unit_Test\Interfaces\Mocker as Mocker_Interface;
use PMC\Unit_Test\Traits\Mocker as Mocker_Trait;
use PMC\Unit_Test\Mocks\Factory;

/**
 * Mocker class to mock user session, whether user is logged into wp admin or front ent, or not logged in
 *
 * Class User
 * @package PMC\Unit_Test\Mocks
 */
final class User
	implements Mocker_Interface {
	use Mocker_Trait;

	private $_user_id       = 0;
	private $_admin_user_id = 0;

	public function provide_service() : string {
		return 'user';
	}

	/**
	 * Mocker user logged in session
	 * @param mixed $user             true | false | 'admin'
	 * @param string $in_admin_screen 'front' when $user is not admin
	 * @return $this
	 */
	public function mock( $user = true, $in_admin_screen = 'front' ) : self {

		if ( in_array( $user, [ 'admin', 'administrator' ], true ) || ( true === $user && in_array( $in_admin_screen, [ 'admin', 'administrator' ], true ) ) ) {
			if ( ! $this->_admin_user_id || ! get_user_by( 'ID', $this->_admin_user_id ) ) {
				$test_factory = Factory::get_instance()->test_factory();
				if ( $test_factory ) {
					$this->_admin_user_id = $test_factory->user->create( array( 'role' => 'administrator' ) );
				}
			}
			if ( 1 === func_num_args() || in_array( $in_admin_screen, [ 'admin', 'administrator' ], true ) ) {
				$in_admin_screen = 'dashboard';
			}
			set_current_screen( $in_admin_screen );
			wp_set_current_user( $this->_admin_user_id );
		} elseif ( true === $user || 'user' === $user ) {
			if ( ! $this->_user_id || ! get_user_by( 'ID', $this->_user_id ) ) {
				$test_factory = Factory::get_instance()->test_factory();
				if ( $test_factory ) {
					$this->_user_id = $test_factory->user->create( array( 'role' => 'subscriber' ) );
				}
			}
			set_current_screen( 'front' );
			wp_set_current_user( $this->_user_id );
		} else {
			set_current_screen( 'front' );
			$GLOBALS['current_user'] = null;
			wp_set_current_user( 0 );
		}

		return $this;

	}

	/**
	 * Return the current mocked user
	 *
	 * @return \WP_User
	 */
	public function get() {
		return wp_get_current_user();
	}

	public function reset() {
		$this->mock( false );
		return $this;
	}

}
