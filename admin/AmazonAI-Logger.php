<?php
/**
 * Logger for AWS AI plugin.
 *
 * @link       amazon.com
 * @since      2.6.2
 *
 */
class AmazonAI_Logger

{

	public function log($log) {

		if (true === WP_DEBUG) {

			$common = new AmazonAI_Common();
			if ( $common->is_logging_enabled() ) {
				error_log($log);
			}

		}
	}

}
