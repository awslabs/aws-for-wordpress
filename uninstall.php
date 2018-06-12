<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Awspolly
 */


error_log("DDD");
 // If uninstall not called from WordPress, then exit.
 if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

	 error_log("Usuwam");
	 exit;
 }
