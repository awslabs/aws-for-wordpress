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

		if (true === WP_DEBUG && apply_filters('amazon_polly_logging_enabled', false)) {
			error_log($log);
		}
	}

}
