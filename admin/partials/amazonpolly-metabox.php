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

	$sample_rate        = get_post_meta( $post->ID, 'amazon_polly_sample_rate', true );
	$global_sample_rate = get_option( 'amazon_polly_sample_rate' );
	if ( strcmp( $sample_rate, '' ) === 0 ) {
		$sample_rate = '22050';
		if ( '' !== $global_sample_rate ) {
			$sample_rate = $global_sample_rate;
		}
	}

	$voice_id = get_post_meta( $post->ID, 'amazon_polly_voice_id', true );

	$global_voice_id = get_option( 'amazon_polly_voice_id' );
	if ( 0 === strcmp( $voice_id, '' ) && '' !== $global_voice_id ) {
		$voice_id = $global_voice_id;
	}

	echo '<p><input type="checkbox" name="amazon_polly_enable" id="amazon_polly_enable" value="1" ' . esc_attr( $checked ) . '/><label for="amazon_polly_enable">Enable Amazon Polly</label> </p>';

	$sample_array = [ '22050', '16000', '8000' ];

	echo '<div id="amazon_polly_post_options" style="' . esc_attr( $post_options_visibility ) . '">';

	echo '<p>Sample rate: <select name="amazon_polly_sample_rate" id="amazon_polly_sample_rate" >';
	foreach ( $sample_array as $rate ) {
		echo '<option value="' . esc_attr( $rate ) . '" ';
		if ( strcmp( $sample_rate, $rate ) === 0 ) {
			echo 'selected="selected"';
		}
		echo '>' . esc_attr( $rate ) . '</option>';
	}
	echo '</select></p>';

	// Basic check to see if the AWS Credentials are valid.
	try {

		if ( empty( get_option( 'amazon_polly_access_key' ) ) ) {

			// Set AWS SDK settings.
			$aws_sdk_config = [
				'region'  => 'us-east-1',
				'version' => 'latest',
			];

		} else {

			$aws_sdk_config = [
				'region'      => 'us-east-1',
				'version'     => 'latest',
				'credentials' => [
					'key'    => get_option( 'amazon_polly_access_key' ),
					'secret' => get_option( 'amazon_polly_secret_key' ),
				],
			];

		}//end if

		$sdk = new Aws\Sdk( $aws_sdk_config );

		$client = $sdk->createPolly();

		$voice_list = $client->describeVoices();
		$disabled   = '';
	} catch ( Exception $e ) {
		$disabled = 'disabled';
		echo 'Please verify your AWS Credentials are accurate<br />';
	}//end try

	/**
	 * Compare two voices for ordering purpose.
	 *
	 * @param           string $voice1                First voice.
	 * @param           string $voice2                Second voice.
	 * @since  1.0.0
	 */
	function sort_voices( $voice1, $voice2 ) {
			return strcmp( $voice1['LanguageName'], $voice2['LanguageName'] );
	}

	echo '<p>Voice name: <select ' . esc_attr( $disabled ) . ' name="amazon_polly_voice_id" id="amazon_polly_voice_id" >';
	usort( $voice_list['Voices'], 'sort_voices' );
	foreach ( $voice_list['Voices'] as $voice ) {
		echo '<option value="' . esc_attr( $voice['Id'] ) . '" ';
		if ( strcmp( $voice_id, $voice['Id'] ) === 0 ) {
			echo 'selected="selected"';
		}
		echo '>' . esc_attr( $voice['LanguageName'] ) . ' - ' . esc_attr( $voice['Id'] ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><button type="button" class="button" id="amazon_polly_price_checker_button" >How much will this cost to convert?</button></p>';
	echo '</div>';

}
