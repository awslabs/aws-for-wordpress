<?php
/**
 * Fired during plugin activation
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 * @author     AWS Labs
 */
class Amazonpolly_Activator {

	/**
	 * Initial configuration of the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// Flush the permalinks to enable the "amazon-pollycast" route.
		$amazon_pollycast = new Amazonpolly_PollyCast();
		$amazon_pollycast->create_podcast();
		flush_rewrite_rules();

		// Default options configurations
		update_option( 'amazon_polly_defconf', 'Amazon Polly enabled' );
		update_option( 'amazon_polly_voice_id', 'Matthew' );
		update_option( 'amazon_polly_sample_rate', '22050' );
		update_option( 'amazon_polly_region', 'us-east-1' );

	}

}
