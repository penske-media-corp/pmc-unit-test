<?php

namespace PMC\Unit_Test\Mocks;

use PMC\Unit_Test\Interfaces\Mocker as Mocker_Interface;
use PMC\Unit_Test\Interfaces\Seeder as Seeder_Interface;
use PMC\Unit_Test\Mocks\Factory;
use PMC\Unit_Test\Traits\Mocker as Mocker_Trait;

/**
 * Class Image
 *
 * @package PMC\Unit_Test\Mocks
 */
class Image extends Post {

	/**
	 * Provide image mocking service
	 *
	 * @return string
	 */
	public function provide_service() {
		return 'image';
	}

	/**
	 * Auto generate and mock the attachment image
	 *
	 * @param Array $args
	 * @return $this
	 */
	public function mock( array $args = [] ) {

		if ( 0 !== func_num_args() || empty( $this->_mocked_post_id ) ) {
			$parent                = $args['parent'] ?? 0;
			$this->_mocked_post_id = Factory::get_instance()->test_object()->attachment->create_upload_object(
				__DIR__ . './data/image.jpg',
				$parent
			);
		}

		return $this;

	}

	public function revision() {
		$function = function () {
			return 1;
		};
		add_filter( 'wp_revisions_to_keep', $function );
		$revision_id = wp_save_post_revision( $this->get() );
		remove_filter( 'wp_revisions_to_keep', $function );
		$this->_ids[] = $revision_id;
		return get_post( $revision_id );
	}

	/**
	 * @throws \Error
	 */
	public function is_amp( $is_amp_endpoint = true ) {
		throw new \Error( sprintf( 'Call to unknown function "is_amp"' ) );
	}

	public function __call( $name, array $arguments ) {
		throw new \Error( sprintf( 'Call to unknown function "%s"', $name ) );
	}

}
