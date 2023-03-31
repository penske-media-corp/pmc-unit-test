<?php
/**
 * Mocker for wp_mail function
 *
 * @package pmc-unit-test
 */

// phpcs:disable Generic.Classes.DuplicateClassName.Found
// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
namespace PMC\Unit_Test\Mocks;

use PMC\Unit_Test\Interfaces\Mocker as MockerInterface;
use PMC\Unit_Test\Traits\Mocker as MockerTrait;

// Workaround compatibility solution between 5.4 & 5.5
// @codeCoverageIgnoreStart
if ( class_exists( \PHPMailer\PHPMailer\PHPMailer::class ) ) {
	class Mock_Mailer extends \PHPMailer\PHPMailer\PHPMailer {
	}
} elseif ( class_exists( \PHPMailer::class ) ) {
	class Mock_Mailer extends \PHPMailer {
	}
} else {
	class Mock_Mailer {
		public function __call( $method, $arguments ) {
		}
		public function __get( $name ) {
			if ( isset( $this->$name ) ) {
				return $this->$name;
			}
			return false;
		}
	}
}
// @codeCoverageEnd

/**
 *
 * Class Mail.
 */
final class Mail
	extends Mock_Mailer
	implements MockerInterface {

	use MockerTrait;

	private $_mocked_send_result = false;
	private $_mailer             = false;

	public function provide_service(): string {
		return 'mail';
	}

	/**
	 * @param array $args
	 *    [
	 *         'send' => true | false,
	 *    ]
	 * @return $this
	 */
	public function mock( $args = [] ): self {
		$this->_mocked_send_result = false;
		if ( isset( $args['send'] ) ) {
			$this->_mocked_send_result = (bool) $args['send'];
		}
		if ( empty( $this->_mailer ) && isset( $GLOBALS['phpmailer'] ) ) {
			$this->_mailer = $GLOBALS['phpmailer'];
		}
		$GLOBALS['phpmailer'] = $this; // phpcs:ignore
		return $this;
	}

	public function send() {
		return $this->_mocked_send_result;
	}

	public function reset() {
		if ( ! empty( $this->_mailer ) ) {
			$GLOBALS['phpmailer'] = $this->_mailer;  // phpcs:ignore
			$this->_mailer        = false;
		}
	}

}

