<?php
/**
 * Enable Polly checkbox for posts.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin/partials
 */

/**
 * Initialize box with 'Enable Amazon Polly' checkbox under the new post form.
 *
 * @since    1.0.0
 * @param      string $post       New post.
 */
function amazon_polly_box_content( $post ) {
	display_polly_gui($post);
	display_translate_gui($post);
}

/**
 * Display Translate GUI on page for saving new post.
 *
 * @since    2.5.0
 * @param      string $post       New post.
 */
function display_translate_gui($post) {
	$common = new AmazonAI_Common();


	$post_source_language = get_post_meta( $post->ID, 'amazon_ai_source_language', true );
	if ( !empty($post_source_language) ) {
		echo '<p><input type="checkbox" name="amazon_ai_deactive_translation" id="amazon_ai_deactive_translation" value="1"/><label for="amazon_polly_enable">Deactive Translation</label> </p>';
	}

	// Check if Translate (Amazon Translate) functionality is enabled.
	if ( $common->is_translation_enabled() ) {


		$number_of_translations = 0;

		foreach ($common->get_all_translatable_languages() as $language_code) {
			$number_of_translations = $number_of_translations + inc_trans($common, $language_code );
		}

		echo '<div id="amazon_ai_translate_gui">';

		if ( ! empty( $number_of_translations ) ) {
			echo '<div id="amazon_polly_trans_div">';
				echo '<p style="display:inline;"><button type="button" class="button button-primary" id="amazon_polly_trans_button">Translate</button></p>';
				echo '<div style="display:inline" id="amazon-polly-trans-info"><p style="display:inline; color: blue;">&nbsp; You will translate it into </p><div id="amazon_polly_number_of_trans" style="display:inline; color: blue;">' . $number_of_translations . '</div><p style="display:inline; color: blue;"> language(s).</p></div>';
				echo '<div id="amazon_polly_trans-progressbar"><div class="amazon_polly_trans-label"></div></div>';
			echo '</div>';
		}

		echo '</div>';

	}
    echo '<p><button type="button" class="button" id="amazon_polly_price_checker_button" >How much will this cost to convert?</button></p>';
    echo '<div id="amazon_ai_plugin_cost_info">';
    if ( $common->is_polly_enabled() ) {
        echo '<p><b>-> Text-To-Speech Functionality</b><p>';
        echo '<p>For Amazon Polly’s Standard voices, <b>the free tier includes 5 million characters per month</b> for speech or Speech Marks requests, for the first 12 months, starting from your first request for speech. For Neural voices, the free tier includes 1 million characters per month for speech or Speech Marks requests, for the first 12 months, starting from your first request. <p>';
        echo '<p>You are billed monthly for the number of characters of text that you processed. Amazon Polly’s Standard voices are priced at $4.00 per 1 million characters for speech or Speech Marks requests (when outside the free tier). Amazon Polly’s Neural voices are priced at $16.00 per 1 million characters for speech or Speech Marks requested (when outside the free tier). <p>';
        echo '<p>When you update your post, plugin needs to conver the whole content to audio again.  <p>';
        echo '<p>You can find full information about pricing of Amazon Polly here: https://aws.amazon.com/polly/pricing/ <p>';
    }
    if ( $common->is_translation_enabled() ) {
        echo '<p><b>-> Translate Functionality</b><p>';
        echo '<p>Your <b>Free Tier is 2 million characters per month for 12 months</b>, starting from the date on which you create your first translation request. When your free usage expires, or if your application use exceeds the free usage tier, you simply pay standard, pay-as-you-go service rates.  <p>';
        echo '<p>After this, you are billed monthly for the total number of characters sent to the API for processing, including white space characters. Amazon Translate is priced at $15 per million characters ($0.000015 per character). <p>';
        echo '<p>You can find full information about pricing of Amazon Translate here: https://aws.amazon.com/translate/pricing/  <p>';
    }
    echo '</div>';
}

/**
 * Display Polly GUI on page for saving new post.
 *
 * @since    2.5.0
 * @param      string $post       New post.
 */
function display_polly_gui($post) {

	$nonce = wp_create_nonce( 'amazon-polly' );

	echo '<input type="hidden" name="amazon-polly-post-nonce" value="' . esc_attr( $nonce ) . '" />';

	$common = new AmazonAI_Common();
	$common->init();


	// Check if Text-To-Speech (Amazon Polly) functionality is enabled.
	if ( $common->is_polly_enabled() ) {

		// Check if Amazon Polly is enabled for specific post.
		// 1 - Means that it's enabled for post
		// 0 - Means that it's not enabled for the post
		// No value - Means that it's new post
		$is_polly_enabled_for_post = get_post_meta( $post->ID, 'amazon_polly_enable', true );
		if ( '1' === $is_polly_enabled_for_post ) {
			$polly_checked = 'checked';
		} elseif ( '0' === $is_polly_enabled_for_post ) {
			$polly_checked	= '';
		} else {
			if ( $common->is_polly_enabled_for_new_posts() ) {
				$polly_checked = 'checked';
			} else {
				$polly_checked	= '';
			}
		}


		$post_options_visibility = '';

		echo '<p><input type="checkbox" name="amazon_polly_enable" id="amazon_polly_enable" value="1"  ' . esc_attr( $polly_checked ) . '/><label for="amazon_polly_enable">Enable Text-To-Speech (Amazon Polly)</label> </p>';
		echo '<div id="amazon_polly_post_options" style="' . esc_attr( $post_options_visibility ) . '">';

		if (!function_exists('sort_polly_voices')) {
			function sort_polly_voices( $voice1, $voice2 ) {
				return strcmp( $voice1['LanguageName'], $voice2['LanguageName'] );
			}
		}


		$voice_id   = $common->get_voice_id();
		$voices = $common->get_polly_voices();
		$language_name = $common->get_source_language_name();

		$voice_id = get_post_meta( $post->ID, 'amazon_polly_voice_id', true );
		$global_voice_id = $common->get_voice_id();

		if ( 0 === strcmp( $voice_id, '' ) && '' !== $global_voice_id ) {
			$voice_id = $global_voice_id;
		}

		usort( $voices['Voices'], 'sort_polly_voices' );

		echo '<p>Voice name: <select name="amazon_polly_voice_id" id="amazon_polly_voice_id" >';
		foreach ( $voices['Voices'] as $voice ) {
			if (strpos($voice['LanguageName'], $language_name) !== false) {
				echo '<option value="' . esc_attr( $voice['Id'] ) . '" ';
				if ( strcmp( $voice_id, $voice['Id'] ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $voice['LanguageName'] ) . ' - ' . esc_attr( $voice['Id'] ) . '</option>';
			}
		}
		echo '</select></p>';


		echo '</div>';

	}


	if (!function_exists('inc_trans')) {
		/**
		 * Method for calculating number of languages to which text should be converted.
		 *
		 * @since    2.5.0
		 * @param      string $post       New post.
		 */
		function inc_trans($common, $language_code ) {

			$is_language_translatable = get_option( 'amazon_polly_trans_langs_' . $language_code, '' );
			$source_language = $common->get_source_language();

			if ( 'on' == $is_language_translatable ) {
				$value = 1;
			} else {
				$value = 0;
			}

			if ( ( 'en' != $source_language ) && ( 'en' == $language_code ) ) {
				$value = 1;
			}

			return $value;
		}
	}

}
