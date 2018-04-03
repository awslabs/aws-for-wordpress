<?php
/**
 * Fired during plugin deactivation
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 * @author     AWS Labs
 */
class Amazonpolly_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_option( 'amazon_polly_s3_bucket' );
		delete_option( 'amazon_polly_valid_keys' );
		delete_option( 'amazon_polly_s3' );
		delete_option( 'amazon_polly_cloudfront' );
		delete_option( 'amazon_polly_autoplay' );
		delete_option( 'amazon_polly_sample_rate' );
		delete_option( 'amazon_polly_voice_id' );
		delete_option( 'amazon_polly_access_key' );
		delete_option( 'amazon_polly_secret_key' );
		delete_option( 'amazon_polly_auto_breaths' );

		// Flush the permalinks to disable the "amazon-pollycast" route.
		flush_rewrite_rules();
	}

}
