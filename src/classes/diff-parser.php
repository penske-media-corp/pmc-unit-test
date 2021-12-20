<?php
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
// phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition

namespace PMC\Unit_Test;

use PMC\Global_Functions\Traits\Singleton;

/**
 * Class Diff_Parser
 * @package PMC\Unit_Test
 */
final class Diff_Parser {
	use Singleton;

	/**
	 * Parse diff file for code added
	 * @param string $file
	 * @return array
	 */
	public function parse( string $file ) : array {
		if ( ! file_exists( $file ) ) {
			return [];
		}
		$fp        = fopen( $file, 'r' );
		$count_old = 0;
		$count_new = 0;
		$line_new  = 0;
		$diff_info = [];
		$filename  = false;
		while ( ( $line = fgets( $fp ) ) !== false ) {
			if ( $count_old > 0 || $count_new > 0 ) {
				switch ( substr( $line, 0, 1 ) ) {
					case '+':
						$count_new -= 1;
						if ( ! empty( $filename ) ) {
							$diff_info[ $filename ][] = $line_new;
						}
						$line_new += 1;
						break;
					case '-':
						$count_old -= 1;
						break;
					default:
						$line_new  += 1;
						$count_old -= 1;
						$count_new -= 1;
						break;
				}
			} else {
				$tokens = explode( ' ', $line );
				switch ( $tokens[0] ) {
					case 'diff':
						$filename               = substr( $tokens[2], 2 );
						$diff_info[ $filename ] = [];
						fgets( $fp ); // index
						fgets( $fp ); // old
						fgets( $fp ); // new
						if ( ! preg_match( '/\.php$/', $filename ) ) {
							$filename = false;
						}
						break;
					case '@@':
						$pair      = explode( ',', $tokens[1] );
						$count_old = intval( $pair[1] );
						$pair      = explode( ',', $tokens[2] );
						$count_new = intval( $pair[1] );
						$line_new  = intval( $pair[0] );
						break;
				}
			}
		}
		return $diff_info;
	}

}
