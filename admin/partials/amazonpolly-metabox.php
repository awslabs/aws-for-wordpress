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
	$nonce = wp_create_nonce( 'amazon-polly' );

	echo '<input type="hidden" name="amazon-polly-post-nonce" value="' . esc_attr( $nonce ) . '" />';

	$check_status = get_post_meta( $post->ID, 'amazon_polly_enable', true );

	if ( '1' === $check_status ) {
		$checked                 = 'checked';
		$post_options_visibility = '';
	} elseif ( '0' === $check_status ) {
		$checked                 = '';
		$post_options_visibility = 'display:none';
	} else {
		$default_configuration = get_option( 'amazon_polly_defconf' );
		if ( 'Amazon Polly enabled' === $default_configuration ) {
			$checked                 = 'checked';
			$post_options_visibility = '';
		} else {
			$checked                 = '';
			$post_options_visibility = 'display:none';
		}
	}

	echo '<p><input type="checkbox" name="amazon_polly_enable" id="amazon_polly_enable" value="1" ' . esc_attr( $checked ) . '/><label for="amazon_polly_enable">Enable Amazon Polly</label> </p>';

	function inc_trans( $language_code ) {
			$value   = get_option( 'amazon_polly_trans_langs_' . $language_code, '' );
			$src_lan = get_option( 'amazon_polly_trans_src_lang', 'en' );
		if ( 'on' == $value ) {
			$inc = 1;
		} else {
			$inc = 0;
		}

		if ( ( 'en' != $src_lan ) && ( 'en' == $language_code ) ) {
			$inc = 1;
		}

			return $inc;
	}

	echo '<div id="amazon_polly_post_options" style="' . esc_attr( $post_options_visibility ) . '">';

	$number_of_translations = '';
	if ( 'on' == get_option( 'amazon_polly_s3' ) ) {
		if ( ! empty( get_option( 'amazon_polly_trans_enabled' ) ) ) {
			$number_of_translations = inc_trans( 'en' ) + inc_trans( 'es' ) + inc_trans( 'de' ) + inc_trans( 'fr' ) + inc_trans( 'pt' );

			if ( ! empty( $number_of_translations ) ) {
				echo '<div id="amazon_polly_trans_div">';
					echo '<p style="display:inline;"><button type="button" class="button button-primary" id="amazon_polly_trans_button">Translate</button></p>';
					echo '<div style="display:inline" id="amazon-polly-trans-info"><p style="display:inline; color: blue;">&nbsp; You will translate it into </p><div id="amazon_polly_number_of_trans" style="display:inline; color: blue;">' . $number_of_translations . '</div><p style="display:inline; color: blue;"> language(s).</p></div>';
					echo '<div id="amazon_polly_trans-progressbar"><div class="amazon_polly_trans-label"></div></div>';
				echo '</div>';
			}
		}
	}

	echo '<p><button type="button" class="button" id="amazon_polly_price_checker_button" >How much will this cost to convert?</button></p>';
	echo '</div>';

}
