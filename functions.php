<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */

/**
 * Provide PHP 7.3 compatibility for array_key_last() function.
 */
if ( ! function_exists( 'array_key_last' ) ) {
	// phpcs:ignore
	function array_key_last( array $array ) {
		if ( ! empty( $array ) ) {
			return key( array_slice( $array, -1, 1, true ) );
		}
	}
}

/**
 * Provide PHP 7.3 compatibility for array_key_first() function.
 */
if ( ! function_exists( 'array_key_first' ) ) {
	// phpcs:ignore
	function array_key_first( array $arr ) {
		foreach ( $arr as $key => $unused ) {
			return $key;
		}
	}
}
