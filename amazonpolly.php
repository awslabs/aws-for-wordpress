<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              amazon.com
 * @since             1.0.0
 * @package           Amazonpolly
 *
 * @wordpress-plugin
 * Plugin Name:       Amazon AI
 * Plugin URI:        https://wordpress.org/plugins/amazon-polly/
 * Description:       Create audio version of your posts, translate them into other languages and create podcasts! Amazon Polly is a service that turns text into lifelike speech. With dozens of voices across a variety of languages, you can select the ideal voice and build engaging speech-enabled applications that work in many different countries. The Amazon Polly plugin for WordPress is a sample application that shows how WordPress creators can easily add Text-to-Speech capabilities to written content with Amazon Polly. You can generate an audio feed for text-based content and insert it into an embedded player to increase the accessibility of your WordPress site. The sample code also enables you to publish podcasts directly from your site and make them available for listeners in the form of podcasts. In addition, the plugin allows to translate your text from one language to another using Amazon Translate– which is a neural machine translation service that delivers fast, high-quality, and affordable language translation. Amazon Translate allows you to localize content - such as websites and applications - for international users, and to easily translate large volumes of text efficiently.
 * Version:           3.0.6
 * Author:            AWS Labs, WP Engine
 * Author URI:        https://aws.amazon.com/
 * License:           GPL-3.0 ONLY
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       amazonpolly
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-amazonpolly-activator.php
 */
function activate_amazonpolly() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-amazonpolly-activator.php';
	Amazonpolly_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-amazonpolly-deactivator.php
 */
function deactivate_amazonpolly() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-amazonpolly-deactivator.php';
	Amazonpolly_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_amazonpolly' );
register_deactivation_hook( __FILE__, 'deactivate_amazonpolly' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-amazonpolly.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_amazonpolly() {

	$plugin = new Amazonpolly();
	$plugin->run();

}
run_amazonpolly();
