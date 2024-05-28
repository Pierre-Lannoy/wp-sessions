<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.3.0
 */

if ( ! function_exists('decalog_get_psr_log_version') ) {
	/**
	 * Get the needed version of PSR-3.
	 *
	 * @return  int  The PSR-3 needed version.
	 * @since 4.0.0
	 */
	function decalog_get_psr_log_version() {
		$required = 1;
		if ( ! defined( 'DECALOG_PSR_LOG_VERSION') ) {
			define( 'DECALOG_PSR_LOG_VERSION', 'V1' );
		}
		switch ( strtolower( DECALOG_PSR_LOG_VERSION ) ) {
			case 'v3':
				$required = 3;
				break;
			case 'auto':
				if ( class_exists( '\Psr\Log\NullLogger') ) {
					$reflection = new \ReflectionMethod(\Psr\Log\NullLogger::class, 'log');
					foreach ( $reflection->getParameters() as $param ) {
						if ( 'message' === $param->getName() ) {
							if ( str_contains($param->getType() ?? '', '|') ) {
								$required = 3;
							}
						}
					}
				}
		}
		return $required;
	}
}

