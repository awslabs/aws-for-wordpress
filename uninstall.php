<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Awspolly
 */

 // If uninstall not called from WordPress, then exit.
 if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	 exit;
 }

// General Options
delete_option('amazon_polly_access_key');
delete_option('amazon_polly_secret_key');

delete_option('amazon_ai_source_language');
delete_option('amazon_polly_region');
delete_option('amazon_polly_s3');
delete_option('amazon_polly_cloudfront');
delete_option('amazon_polly_posttypes');
delete_option('amazon_polly_poweredby');
delete_option('amazon_ai_logging');

// Text-To-Speech Options
delete_option('amazon_polly_sample_rate');
delete_option('amazon_polly_voice_id');
delete_option('amazon_polly_auto_breaths');
delete_option('amazon_polly_ssml');
delete_option('amazon_polly_lexicons');
delete_option('amazon_polly_speed');
delete_option('amazon_polly_position');
delete_option('amazon_polly_player_label');
delete_option('amazon_polly_defconf');
delete_option('amazon_polly_autoplay');
delete_option('amazon_polly_update_all');
delete_option('amazon_polly_add_post_title');
delete_option('amazon_polly_add_post_excerpt');
delete_option('amazon_ai_medialibrary_enabled');
delete_option('amazon_ai_skip_tags');

// Translate Options
delete_option('amazon_polly_trans_enabled');
delete_option('amazon_polly_trans_src_lang');
delete_option('amazon_ai_audio_for_translation_enabled');

$languages = array('da','nl','zh','cs','en','fi','fr','de','he','it','id','ja','ko','nb','pl','pt','ro','ru','es','sv','tr','cy');
foreach( $languages as $language_code ){
  delete_option('amazon_polly_trans_langs_' . $language_code, 'strval');
  delete_option('amazon_polly_trans_langs_' . $language_code . '_voice', 'strval');
  delete_option('amazon_polly_trans_langs_' . $language_code . '_label', 'strval');
  delete_option('amazon_polly_trans_langs_' . $language_code . '_display', 'strval');
}

// Podcast Options
delete_option('amazon_polly_podcast_email');
delete_option('amazon_polly_podcast_category');
delete_option('amazon_polly_podcast_explicit');
delete_option('amazon_polly_podcast_icon');
delete_option('amazon_polly_podcast_feedsize');
delete_option('amazon_polly_podcast_post_cat');
delete_option('amazon_polly_podcast_author');
delete_option('amazon_polly_podcast_https');
