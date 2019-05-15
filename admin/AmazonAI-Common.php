<?php
/**
 * Common operations used by the Amazon AI plugin.
 *
 * @link       amazon.com
 * @since      2.5.0
 *
 */
class AmazonAI_Common

{

	// Information about languages supported by the Amazon AI plugin
	private $languages = [
		['code' => 'ar', 'name' => 'Arabic', 'transable' => '1', 'polly' => '1'],
		['code' => 'da', 'name' => 'Danish', 'transable' => '1', 'polly' => '1'],
		['code' => 'nl', 'name' => 'Dutch', 'transable' => '1', 'polly' => '1'],
		['code' => 'zh', 'name' => 'Chinese', 'transable' => '1', 'polly' => '1'],
		['code' => 'cs', 'name' => 'Czech', 'transable' => '1', 'polly' => ''],
		['code' => 'en', 'name' => 'English', 'transable' => '1', 'polly' => '1'],
		['code' => 'fi', 'name' => 'Finish', 'transable' => '1', 'polly' => ''],
		['code' => 'fr', 'name' => 'French', 'transable' => '1', 'polly' => '1'],
		['code' => 'de', 'name' => 'German', 'transable' => '1', 'polly' => '1'],
		['code' => 'he', 'name' => 'Hebrew', 'transable' => '1', 'polly' => ''],
		['code' => 'hi', 'name' => 'Hindi', 'transable' => '1', 'polly' => ''],
		['code' => 'it', 'name' => 'Italian', 'transable' => '1', 'polly' => '1'],
		['code' => 'id', 'name' => 'Indonesian', 'transable' => '1', 'polly' => ''],
		['code' => 'ja', 'name' => 'Japanese', 'transable' => '1', 'polly' => '1'],
		['code' => 'ko', 'name' => 'Korean', 'transable' => '1', 'polly' => '1'],
		['code' => 'ms', 'name' => 'Malay', 'transable' => '1', 'polly' => ''],
		['code' => 'no', 'name' => 'Norwegian', 'transable' => '1', 'polly' => '1'],
		['code' => 'fa', 'name' => 'Persian', 'transable' => '1', 'polly' => ''],
		['code' => 'pl', 'name' => 'Polish', 'transable' => '1', 'polly' => '1'],
		['code' => 'pt', 'name' => 'Portuguese', 'transable' => '1', 'polly' => '1'],
		['code' => 'ro', 'name' => 'Romanian', 'transable' => '', 'polly' => '1'],
		['code' => 'ru', 'name' => 'Russian', 'transable' => '1', 'polly' => '1'],
		['code' => 'es', 'name' => 'Spanish', 'transable' => '1', 'polly' => '1'],
		['code' => 'sv', 'name' => 'Swedish', 'transable' => '1', 'polly' => '1'],
		['code' => 'tr', 'name' => 'Turkish', 'transable' => '1', 'polly' => '1'],
		['code' => 'cy', 'name' => 'Welsh', 'transable' => '', 'polly' => '1']
	];

	public function prepare_paragraphs($post_id) {

		$clean_content = '';
		$post_content = get_post_field('post_content', $post_id);
		$paragraphs = explode("\n", $post_content);

		foreach($paragraphs as $paragraph) {
			$clean_paragraph = $this->clean_paragraph($paragraph);

			$clean_content = $clean_content . "\n" . $clean_paragraph;
		}


		return $clean_content;
	}

	public function get_subscribe_link() {

		$value = get_option( 'amazon_polly_podcast_button_link' );

		if (empty($value)) {
			$value = esc_attr( get_feed_link( 'amazon-pollycast' ) );
		}

		return esc_attr($value);

	}


	public function get_podcast_author() {

		$value = get_option( 'amazon_polly_podcast_author' );

		if( empty($value) ) {
			$value = 'Author';
		}

		return $value;

	}

	public function clean_paragraph($paragraph) {

		$clean_text = $paragraph;
		$clean_text = do_shortcode($clean_text);
		$clean_text = str_replace('&nbsp;', ' ', $clean_text);

		$is_ssml_enabled = $this->is_ssml_enabled();
	  if ($is_ssml_enabled) {
	    $clean_text = $this->encode_ssml_tags($clean_text);
	  }

		$clean_text = strip_tags($clean_text, '<break>');
		$clean_text = esc_html($clean_text);


	  $clean_text = str_replace('&nbsp;', ' ', $clean_text);
	  $clean_text = preg_replace("/https:\/\/([^\s]+)/", "", $clean_text);
		$clean_text = html_entity_decode($clean_text, ENT_QUOTES, 'UTF-8');
	  $clean_text = str_replace('&', ' and ', $clean_text);
	  $clean_text = str_replace('<', ' ', $clean_text);
	  $clean_text = str_replace('>', ' ', $clean_text);

		return $clean_text;
	}

	public function get_feed_size() {
		$feedsize = get_option( 'amazon_polly_podcast_feedsize' );

		$value = intval( $feedsize );

		if ( empty( $value ) ) {
			$value = 20;
		}

		if ( intval( $value ) < 1 ) {
			$value = 1;
		}

		if ( intval( $value ) > 1000 ) {
			$value = 1000;
		}

		update_option( 'amazon_polly_podcast_feedsize', $value );

		return $value;

	}

	public function get_language_name($provided_langauge_code) {

		foreach ($this->languages as $language_data) {
			$language_code = $language_data['code'];
			$language_name = $language_data['name'];

			if ($language_code === $provided_langauge_code) {
				return $language_name;
			}
		}

		return "N/A";
	}

	public function get_all_languages() {
		$supported_languages = [];

		foreach ($this->languages as $language_data) {
			$language_code = $language_data['code'];
			array_push($supported_languages, $language_code);
		}

		return $supported_languages;
	}

	public function get_all_translable_languages() {
		$supported_languages = [];

		foreach ($this->languages as $language_data) {
			$language_code = $language_data['code'];
			$is_language_supported = $language_data['transable'];

			if ( !empty($is_language_supported) ) {
				array_push($supported_languages, $language_code);
			}
		}

		return $supported_languages;
	}

	public function get_language_display($language_code) {

		$language_display_option = 'amazon_polly_trans_langs_' . $language_code . '_display';
		$value = get_option( $language_display_option );
		if ( empty($value) ) {
			$value = 'Flag';
		}

		return $value;

	}

	public function get_language_label($language_code) {

		$language_label_option = 'amazon_polly_trans_langs_' . $language_code . '_label';
		$value = get_option( $language_label_option );
		if ( empty($value) ) {
			$value = strtoupper( $language_code );
		}

		return $value;

	}

	public function get_all_polly_languages() {


		$supported_languages = [];

		foreach ($this->languages as $language_data) {
			$language_code = $language_data['code'];
			$is_language_supported = $language_data['polly'];

			if ( !empty($is_language_supported) ) {
				array_push($supported_languages, $language_code);
			}
		}

		return $supported_languages;
	}


	public function get_source_language_name() {

    $selected_source_language = $this->get_source_language();

    foreach ($this->languages as $language_data) {
      $language = $language_data['code'];

      if (strcmp($selected_source_language, $language) === 0) {
        return $language_data['name'];
      }
    }

    return '';
  }

	private $sdk;
	private $polly_client;
	private $translate_client;
	private $s3_handler;
	private $local_file_handler;
	private $translate;
	private $logger;

	/**
	 * Creates SDK objects for the plugin.
	 *
	 * @since    2.5.0
	 */
	public function __construct() {
		$this->logger = new AmazonAI_Logger();
	}

	public function init() {
		$aws_sdk_config = $this->get_aws_sdk_config();
		$this->sdk = new Aws\Sdk($aws_sdk_config);
		$this->polly_client = $this->sdk->createPolly();
		$this->translate_client = $this->sdk->createTranslate();

		$this->s3_handler = new AmazonAI_S3FileHandler();
		$this->local_file_handler = new AmazonAI_LocalFileHandler();

		$this->s3_handler->set_s3_client($this->sdk->createS3());
		$this->translate = new AmazonAI_Translator();
	}

	public function get_translate_client() {
		return $this->translate_client;
	}

	/**
	 * Method returns file handler which is reponsible for communicating with proper storage location.
	 *
	 * @since       2.0.3
	 */
	public function get_file_handler() {

		$is_s3_enabled = $this->is_s3_enabled();
		if ( $is_s3_enabled ) {
		  return $this->s3_handler;
		} else {
			return $this->local_file_handler;
		}

	}

	public function get_polly_client() {
		return $this->polly_client;
	}

	/**
	 * Method removes ID3 tag from audio file
	 *
	 * @param           string $filename                 File for which tag should be removed.
	 * @since           1.0.0
	 */
	public function remove_id3( $filename ) {

		// Temporary file - without IDv3 tag.
		$temp_filename = $filename . 'temp';

		// Original file with IDv3 tag.
		$source_file = fopen( $filename, 'r+b' );

		// IDv3 header has got 10 bytes.
		$id3_header = fread( $source_file, 10 );

		// Calculating the total size of IDv3 tag.
		$int_value        = 0;
		$byte_word        = substr( $id3_header, 6, 4 );
		$byte_word_length = strlen( $byte_word );
		for ( $i = 0; $i < $byte_word_length; $i++ ) {
			$int_value += ( ord( $byte_word{$i} ) & 0x7F ) * pow( 2, ( $byte_word_length - 1 - $i ) * 7 );
		}
		$offset = ( (int) $int_value ) + 10;

		// Recreating file without the IDv3 tag bytes.
		rewind( $source_file );
		fseek( $source_file, $offset );
		$temp_file = fopen( $temp_filename, 'w+b' );

		while ( $buffer = fread( $source_file, 32768 ) ) {
			fwrite( $temp_file, $buffer, strlen( $buffer ) );
		}

		// Swapping files.
		fclose( $temp_file );
		unlink( $filename );
		rename( $temp_filename, $filename );

	}

	public function startsWith ($string, $beginning) {
	    $len = strlen($beginning);
	    return (substr($string, 0, $len) === $beginning);
	}

	public function endsWith($string, $ending) {
	    $len = strlen($ending);
	    if ($len == 0) {
	        return true;
	    }
	    return (substr($string, -$len) === $ending);
	}


	/**
	 * Checks if auto breaths are enabled.
	 *
	 * @since  1.0.7
	 */
	public function is_auto_breaths_enabled() {
		$value = get_option( 'amazon_polly_auto_breaths', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	public function deactive_translation_for_post($post_id) {

		delete_post_meta($post_id, 'amazon_ai_source_language');

		$languages_code = array_column($this->languages, 'code');
		foreach ( $languages_code as $language_code ) {
			delete_post_meta($post_id, 'amazon_polly_transcript_' . $language_code);
			delete_post_meta($post_id, 'amazon_polly_transcript_title_' . $language_code);
		}

		$this->delete_post($post_id);
	}

	/**
	 * Returns source language.
	 *
	 * @since  2.0.0
	 */
	public function get_post_source_language($post_id) {
		$value = get_post_meta( $post_id, 'amazon_ai_source_language', true );

		if (empty($value)) {
			$value = $this->get_source_language();
		}

		return $value;
	}

	/**
	 * Returns source language.
	 *
	 * @since  2.0.0
	 */
	public function get_source_language()
	{
		$value = get_option('amazon_ai_source_language', 'en');
		if (empty($value)) {
			$value = 'en';
		}

		return $value;
	}

	public function replace_if_empty($value, $new_value)
	{
		if (!empty($value)) {
			return $value;
		}
		else {
			return $new_value;
		}
	}


	public function is_polly_enabled_for_new_posts() {
		if ( $this->is_polly_enabled() ) {
			$default_configuration = get_option( 'amazon_polly_defconf' );
			if ( 'Amazon Polly enabled' === $default_configuration ) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function is_audio_download_enabled() {
			$value = $this->checked_validator('amazon_ai_download_enabled');
			if ('checked' == trim($value)) {
				return true;
			} else {
				return false;
			}
	}

	/**
	 * Validates if logging is enabled.
	 *
	 * @since  2.6.1
	 */
	public function is_logging_enabled() {
			$value = $this->checked_validator('amazon_ai_logging');
			if ('checked' == trim($value)) {
				return true;
			} else {
				return false;
			}
	}

	/**
	 * Validates if Amazon Polly support is enabled.
	 *
	 * @since  2.5.0
	 */
	public function is_polly_enabled() {
			$value = $this->checked_validator('amazon_ai_polly_enable');
			if ('checked' == trim($value)) {
				return true;
			} else {
				return false;
			}
	}

	public function is_audio_for_translations_enabled() {
		if ( $this->is_polly_enabled() ) {
			$value = $this->checked_validator('amazon_ai_audio_for_translation_enabled');
			if ('checked' == trim($value)) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function validate_amazon_translate_access() {
		try {
			$this->translate->is_translate_accessible($this->translate_client);
			return true;
		} catch(TranslateAccessException $e) {
			$this->show_error_notice("notice-error", "Amazon Translate service is not reachable!");
			return false;
		}

		return true;
	}

	/**
	 * Render the enable Translation input.
	 *
	 * @since  2.0.0
	 */
	public function is_translation_enabled()
	{
				if ($this->is_s3_enabled()) {
					$start_value = $this->checked_validator('amazon_polly_trans_enabled');
					$translate_accessible = true;
					$supported_regions = array(
						'us-east-1',
						'us-east-2',
						'us-west-2',
						'eu-west-1'
					);
					$selected_region = $this->get_aws_region();
					if (in_array($selected_region, $supported_regions)) {
						if ('checked' == trim($start_value)) {
							if ($translate_accessible) {
								return true;
							}
						}
					}
			}


		return false;
	}


	public function get_s3_object_link($post_id, $language) {

		$file_name	= 'amazon_polly_' . $post_id . $language . '.mp3';
		$s3BucketName = $this->get_s3_bucket_name();
		$cloudfront_domain_name = get_option( 'amazon_polly_cloudfront' );

		if ( get_option('uploads_use_yearmonth_folders') ) {
			$key = get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . '/' . $file_name;
		} else {
			$key = $file_name;
		}

		if ( empty( $cloudfront_domain_name ) ) {

			$selected_region = $this->get_aws_region();

			$audio_location_link = 'https://s3.' . $selected_region . '.amazonaws.com/' . $s3BucketName . '/' . $key;
		} else {
			$audio_location_link = 'https://' . $cloudfront_domain_name . '/' . $key;
		}

		return $audio_location_link;

	}



	/**
	 * Validates if AWS configuration is correct and AWS can be reached.
	 *
	 * @since    2.5.0
	 */
	public function validate_amazon_polly_access()
	{
		try {
			$this->check_aws_access();

			// Checks if S3 should be used for storing files.

			$is_s3_enabled = $this->is_s3_enabled();
			if ($is_s3_enabled) {
				try {

					// Checks if S3 bucket can be access.

					$accessible = $this->s3_handler->check_if_s3_bucket_accessible();
					if (!$accessible) {
						$this->s3_handler->create_s3_bucket();
						$this->show_error_notice("notice-info", "New S3 bucket created!");
					}
				}

				catch(S3BucketNotAccException $e) {
					$this->show_error_notice("notice-info", "Can't access S3 bucket, will try to create new one!");

					// If S3 bucket is not accessible, we will try to create new one.

					$this->s3_handler->create_s3_bucket();
					$this->show_error_notice("notice-info", "New S3 bucket created!");
				}
			}
		}

		catch(CredsException $e) {
			$this->deactivate_all();
			$this->show_error_notice("notice-error", "Can't connect to AWS! Check your credentials and make sure your AWS accout is active!");
			return false;
		}

		catch(S3BucketNotCreException $e) {
			$this->show_error_notice("notice-error", "Could not create S3 bucket!");
			return false;
		}

		catch(Exception $e) {
			$this->deactivate_all();
			$this->show_error_notice("notice-error", "Not known error!");
			return false;
		}

		return true;
	}

	public function deactivate_all() {
		$this->deactivate_polly();
		$this->deactivate_translate();
		$this->deactivate_podcast();
	}


	public function deactivate_polly() {
		update_option( 'amazon_ai_polly_enable', '' );
	}

	public function deactivate_translate() {
		update_option( 'amazon_polly_trans_enabled', '' );
	}

	public function deactivate_podcast() {
		update_option( 'amazon_polly_podcast_enabled', '' );
	}


	public function show_error_notice($type, $message)
	{
		add_action('admin_notices',
		function () use($type, $message)
		{
?>
						<div class="notice  <?php
			echo $type ?>  is-dismissible">
							<p><?php
			_e($message, 'amazon_ai'); ?></p>
						</div>

			<?php
		});
	}


	public function get_sample_rate() {
		$sample_rate = get_option('amazon_polly_sample_rate');
		if (empty($sample_rate)) {
			$sample_rate = '22050';
			update_option('amazon_polly_sample_rate', $sample_rate);
		}

		$this->logger->log(sprintf('%s Sample rate: %s ', __METHOD__, $sample_rate));

		return $sample_rate;
	}

	public function get_voice_id() {
		$voice_id = get_option('amazon_polly_voice_id');
		if (empty($voice_id)) {
			$voice_id = 'Matthew';
			update_option('amazon_polly_voice_id', $voice_id);
		}

		return $voice_id;
	}

	/**
	 * Returns the name of the AWS region, which should be used by the plugin.
	 *
	 * @since    2.5.0
	 */
	public function get_aws_region()
	{
		$region = get_option('amazon_polly_region');
		if (empty($region)) {
			update_option('amazon_polly_region', 'us-east-1');
			$region = 'us-east-1';
		}

		return $region;
	}

	public function if_translable_enabled_for_language($language_code) {
		$source_language_code = $this->get_source_language();
		$value = $this->check_if_language_is_checked($language_code, $source_language_code);
		if (empty($value)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if checkbox should be checked for specific language.
	 *
	 * @since  2.0.0
	 */
	public function check_if_language_is_checked($language_code, $source_language_code)
	{
		#Some translations between languages are not supported by the service.
		#Details: https://docs.aws.amazon.com/translate/latest/dg/pairs.html
		if (!$this->is_translation_supported($source_language_code, $language_code)) {
			return '';
		}

		$option = 'amazon_polly_trans_langs_' . $language_code;

		$value = get_option($option, '');

		if (empty($value)) {
			return '';
		}
		else {
			return ' checked ';
		}
	}

	/**
	 * Get S3 bucket name. The method uses filter 'amazon_polly_s3_bucket_name,
	 * which allows to use customer S3 bucket name instead of default one.
	 *
	 * @since  1.0.6
	 */
	public function get_s3_bucket_name()
	{
		$s3_bucket_name = $this->s3_handler->get_bucket_name();
		return $s3_bucket_name;
	}

	public function get_polly_voices()
	{
		$voices = $this->polly_client->describeVoices();
		return $voices;
	}

	/**
	 * Return post type value.
	 *
	 * @since  1.0.7
	 */
	public function get_posttypes()
	{
		$posttypes = get_option('amazon_polly_posttypes', 'post');
		$posttypes = str_replace(",", " ", $posttypes);
		$posttypes = preg_replace('!\s+!', ' ', $posttypes);
		update_option('amazon_polly_posttypes', $posttypes);

		return $posttypes;
	}

	/**
	 * Checks if pollycast is enabled.
	 *
	 * @since  1.0.7
	 */
	public function is_podcast_enabled()
	{
		$value = get_option('amazon_polly_podcast_enabled', 'on');
		if (empty($value)) {
			$result = false;
		}
		else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Return speed for audio files.
	 *
	 * @since  1.0.5
	 */
	public function get_audio_speed()
	{
		$speed = get_option('amazon_polly_speed');
		if (empty($speed)) {
			$speed = '100';
		}

		if (intval($speed) < 20) {
			$speed = '20';
		}

		if (intval($speed) > 200) {
			$speed = '200';
		}

		update_option('amazon_polly_speed', $speed);
		return $speed;
	}

	/**
	 * Method returns lexicons specified in plugin configuration.
	 *
	 * @since  1.0.12
	 */
	public function get_lexicons()
	{
		$lexicons = get_option('amazon_polly_lexicons', '');
		$lexicons = trim($lexicons);
		return $lexicons;
	}

	/**
	 * Check if Powered by AWS option is enabled
	 *
	 * @since  1.0.7
	 */
	public function is_poweredby_enabled()
	{
		$poweredby = get_option('amazon_polly_poweredby', 'on');

		if (empty($poweredby)) {
			$result = false;
		}
		else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Check if SSML is enabled.
	 *
	 * @since  1.0.7
	 */
	public function is_ssml_enabled()
	{
		$ssml_enabled = get_option('amazon_polly_ssml', 'on');
		if (empty($ssml_enabled)) {
			$result = false;
		}
		else {
			$result = true;
		}

		$is_s3_enabled = $this->is_s3_enabled();
		if ($is_s3_enabled) {
			return $result;
		}

		return false;
	}

	/**
	 * Utility function which checks if checkbox for option input should be checked.
	 *
	 * @param       string $option Name of the option which should be checked.
	 * @since  2.0.0
	 */
	public function checked_validator($option)
	{
		$option_value = get_option($option, 'on');
		if (empty($option_value)) {
			return '';
		}
		else {
			return ' checked ';
		}
	}

	/**
	 * Checks if Media Libary support is enabled.
	 *
	 * @since  2.5.0
	 */
	public function is_medialibrary_enabled()
	{
		$value = get_option('amazon_ai_medialibrary_enabled');
		if (empty($value)) {
			$result = false;
		}
		else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Checks if 'Show Subscribe button' is enabled.
	 *
	 * @since  2.6.3
	 */
	public function is_subscribe_button_enabled()
	{
		if ($this->is_podcast_enabled()) {
			$value = get_option('amazon_polly_podcast_button');
			if (empty($value)) {
				$result = false;
			}
			else {
				$result = true;
			}
		} else {
			$result = false;
		}

		return $result;
	}

	/**
	 * Checks if S3 storage is enabled.
	 *
	 * @since  1.0.7
	 */
	public function is_s3_enabled()
	{
		$value = get_option('amazon_polly_s3', 'on');
		if (empty($value)) {
			$result = false;
		}
		else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Check  connectivity with AWS can be eastablished.
	 *
	 * @since    2.5.0
	 */
	private function check_aws_access()
	{
		try {
			$voice_list = $this->polly_client->describeVoices();
			update_option('amazon_polly_valid_keys', '1');
			return true;
		}

		catch(Exception $e) {
			update_option('amazon_polly_valid_keys', '0');
			throw new CredsException('Could not connect to AWS');
		}
	}

	/**
	 * Returns AWS SDK configuration to allow connection with AWS account.
	 *
	 * @since    2.5.0
	 */
	private function get_aws_sdk_config()
	{
		$aws_access_key = get_option('amazon_polly_access_key');
		$aws_secret_key = get_option('amazon_polly_secret_key');
		if (empty($aws_access_key)) {
			$aws_sdk_config = array(
				'region' => $this->get_aws_region() ,
				'version' => 'latest',
			);
		}
		else {
			$aws_sdk_config = array(
				'region' => $this->get_aws_region() ,
				'version' => 'latest',
				'credentials' => array(
					'key' => $aws_access_key,
					'secret' => $aws_secret_key,
				) ,
			);
		}

		return $aws_sdk_config;
	}


	/**
	 * Calculate the total price of converting all posts into audio.
	 *
	 * @since  1.0.0
	 */
	public function get_price_message_for_update_all()
	{
		$post_types_supported = $this->get_posttypes_array();
		$number_of_characters = 0;
		$posts_per_page = apply_filters('amazon_polly_posts_per_page', 5);
		$count_posts = wp_count_posts()->publish;
		$max_count_posts = 100;

		// Retrieving the number of characters in all posts.

		$paged = 0;
		$post_count = 0;
		do {
			$paged++;
			$wp_query = new WP_Query(array(
				'posts_per_page' => $posts_per_page,
				'post_type' => $post_types_supported,
				'fields' => 'ids',
				'paged' => $paged,
			));
			$number_of_posts = $wp_query->max_num_pages;
			while ($wp_query->have_posts()) {
				$post_count++;
				$wp_query->the_post();
				$post_id = get_the_ID();
				$clean_text = $this->clean_text($post_id, true, false);
				$post_sentences = $this->break_text($clean_text);
				if (!empty($post_sentences)) {
					foreach($post_sentences as $sentence) {
						$sentence = str_replace('**AMAZONPOLLY*SSML*BREAK*time=***1s***SSML**', '', $sentence);
						$sentence = str_replace('**AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML**', '', $sentence);
						$number_of_characters+= strlen($sentence);
					}
				}
			}

			// If we reached the number of posts which we wanted to read, we stop
			// reading next posts.

			if ($post_count >= $max_count_posts) {
				break;
			}
		}

		while ($paged < $number_of_posts);

		// Price for converting single character according to Amazon Polly pricing.

		$amazon_polly_price = 0.000004;

		// Estimating average number of characters per post.

		if (0 !== $post_count) {
			$post_chars_count_avg = $number_of_characters / $post_count;
		}
		else {
			$post_chars_count_avg = 0;
		}

		// Estimating the total price of convertion of all posts.

		$total_price = 2 * $amazon_polly_price * $count_posts * $post_chars_count_avg;
		$message = 'You are about to convert ' . number_format($count_posts, 0, '.', ',') . ' pieces of text-based content, which totals approximately ' . number_format($number_of_characters, 0, '.', ',') . ' characters. Based on the Amazon Polly pricing ($4 dollars per 1 million characters) it will cost you about $' . $total_price . ' to convert all of your content into to speech-based audio. Some or all of your costs might be covered by the Free Tier (conversion of 5 million characters per month for free, for the first 12 months, starting from the first request for speech). Learn more https://aws.amazon.com/polly/';
		return $message;
	}

	/**
	 * Method prepare WP_Filesystem variable for interacting with local file system.
	 *
	 * @since    1.0.0
	 */
	public function prepare_wp_filesystem() {
		/** Ensure WordPress Administration File API is loaded as REST requests do not load the file API */
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$url   = wp_nonce_url( admin_url( 'post-new.php' ) );
		$creds = request_filesystem_credentials( $url );

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url );
			return true;
		}

		global $wp_filesystem;

		return $wp_filesystem;
	}

	/**
	 * Method breakes input text into smaller parts.
	 *
	 * @since       1.0.0
	 * @param       string $text     Text which should be broken.
	 */
	public function break_text($text)
	{
		$text = str_replace('-AMAZONPOLLY-ONLYAUDIO-START-', '', $text);
		$text = str_replace('-AMAZONPOLLY-ONLYAUDIO-END-', '', $text);
		$text = preg_replace('/-AMAZONPOLLY-ONLYWORDS-START-[\S\s]*?-AMAZONPOLLY-ONLYWORDS-END-/', '', $text);
		$parts = [];
		if (!empty($text)) {
			$part_id = 0;
			$paragraphs = explode("\n", $text);
			foreach($paragraphs as $paragraph) {
				$paragraph_size = strlen(trim($paragraph));
				if ($paragraph_size > 0) {
					if ($paragraph_size <= 2800) {
						$parts[$part_id] = $paragraph . ' **AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML** ';
						$part_id++;
					}
					else {
						$words = explode(' ', $paragraph);
						$current_part = '';
						$last_part = '';
						foreach($words as $word) {
							$word_length = strlen($word);
							$current_part_length = strlen($current_part);
							if ($word_length + $current_part_length < 2800) {
								$current_part = $current_part . $word . ' ';
								$last_part = $current_part;
							}
							else {
								$current_part = $current_part . $word . ' ';
								$parts[$part_id] = $current_part;
								$part_id++;
								$current_part = '';
								$last_part = '';
							}
						}

						$parts[$part_id] = $last_part . ' **AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML** ';
						$part_id++;
					} //end if
				} //end if
			} //end foreach
		} //end if

		// Modify speed

		$parts = $this->modify_speed($parts);

		$logger = new AmazonAI_Logger();

		foreach($parts as $part) {
			$logger->log(sprintf('%s <<< PART >>> ', __METHOD__));
			$logger->log(sprintf('%s', $part));
		}

		return $parts;
	}

	/**
	 * Method update sentences (input of the method), and modify their speed,
	 * by adding SSML prosody tag for each sentence.
	 *
	 * @param           string $sentences                 Sentences which should be updated.
	 * @since           1.0.5
	 */
	public function modify_speed($sentences)
	{
		$new_sentences = [];
		$new_sentence_id = 0;
		$speed = $this->get_audio_speed();
		if (100 !== $speed) {
			foreach($sentences as $sentence) {
				$new_sentence = '<prosody rate="' . $speed . '%">' . $sentence . '</prosody>';
				$new_sentences[$new_sentence_id] = $new_sentence;
				$new_sentence_id++;
			}
		}

		return $new_sentences;
	}

	public function modify_sentence_speed($sentence)
	{

		$speed = $this->get_audio_speed();
		if (100 !== $speed) {
				$sentence = '<prosody rate="' . $speed . '%">' . $sentence . '</prosody>';
		}


		return $sentence;
	}

	/**
	 * Checks if post title should be added.
	 *
	 * @since  1.0.7
	 */
	public function is_excerpt_adder_enabled()
	{
		$value = get_option('amazon_polly_add_post_excerpt', 'on');
		if (empty($value)) {
			$result = false;
		}
		else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Encode SSML tags.
	 *
	 * @since  1.0.7
	 * @param  string $text text which should be decoded.
	 */
	public function decode_ssml_tags( $text ) {

		$text = preg_replace( '/(\*\*AMAZONPOLLY\*SSML\*BREAK\*)(.*?)(\*\*\*)(.*?)(\*\*\*SSML\*\*)/', '<break $2"$4"/>', $text );

		return $text;
	}

	/**
	 * Method retrievies post which ID was provided, and clean it.
	 *
	 * @since       1.0.12
	 * @param       string $post_id     ID of the post for which test (content) should be prepapred for conversion.
	 */
	public function clean_text($post_id, $with_title, $only_title)
	{

		#$this->logger->log(sprintf('%s Cleaning text (%s, %s) ', __METHOD__, $with_title, $only_title));

		$clean_text = '';

		// Depending on the plugin configurations, post's title will be added to the audio.
		if ($with_title) {
			if ($this->is_title_adder_enabled()) {
				$clean_text = get_the_title($post_id) . '. **AMAZONPOLLY*SSML*BREAK*time=***1s***SSML** ';
			}
		}


		// Depending on the plugin configurations, post's excerpt will be added to the audio.

		if ($this->is_excerpt_adder_enabled()) {
			$my_excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $post_id));
			$clean_text = $clean_text . $my_excerpt . ' **AMAZONPOLLY*SSML*BREAK*time=***1s***SSML** ';
		}

		$clean_text = $clean_text . get_post_field('post_content', $post_id);
		$clean_text = apply_filters('amazon_polly_content', $clean_text);

		if ($only_title) {
			$clean_text = get_the_title($post_id);
		}

		$clean_text = str_replace('&nbsp;', ' ', $clean_text);
		$clean_text = do_shortcode($clean_text);

		$clean_text = $this->skip_tags($clean_text);

		$is_ssml_enabled = $this->is_ssml_enabled();
		if ($is_ssml_enabled) {
			$clean_text = $this->encode_ssml_tags($clean_text);
		}

		// Creating text description for images
		$clean_text = $this->replace_images($clean_text);
		$clean_text = strip_tags($clean_text, '<break>');
		$clean_text = esc_html($clean_text);


		$clean_text = str_replace('&nbsp;', ' ', $clean_text);
		$clean_text = preg_replace("/https:\/\/([^\s]+)/", "", $clean_text);
		$clean_text_temp = '';

		$paragraphs = explode("\n", $clean_text);
		foreach($paragraphs as $paragraph) {
			$paragraph_size = strlen(trim($paragraph));
			if ($paragraph_size > 0) {
				$clean_text_temp = $clean_text_temp . "\n" . $paragraph;
			}
		}

		$clean_text = $clean_text_temp;
		$clean_text = html_entity_decode($clean_text, ENT_QUOTES, 'UTF-8');
		$clean_text = str_replace('&', ' and ', $clean_text);
		$clean_text = str_replace('<', ' ', $clean_text);
		$clean_text = str_replace('>', ' ', $clean_text);


		return $clean_text;
	}

	private function replace_images($clean_text) {

		//$new_clean_text = preg_replace('/<img.*?alt="(.*?)"[^\>]+>/', 'Image: $1.', $clean_text);
		$new_clean_text = preg_replace('/<img.*?alt="(.*?)"[^\>]+>/', '$1', $clean_text);

		return $new_clean_text;

	}

	/**
	 * Run when deleting a post.
	 *
	 * @param      string $post_id   ID of the post which is gonna to be deleted.
	 * @since    1.0.0
	 */
	public function delete_post( $post_id ) {
		// Check if this isn't an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$this->delete_post_audio( $post_id );
	}

	/**
	 * Delets audio for specific post.
	 *
	 * @param string $post_id ID of the post for which audio should be deleted.
	 * @since 1.0.0
	 */
	public function delete_post_audio( $post_id ) {
		try {
			// Deleting audio file.
			$this->init();

			// Validate if this post which is being saved is one of supported types. If not, return.
			$post_types_supported = $this->get_posttypes_array();
			$post_type = get_post_type($post_id);
			if (!in_array($post_type, $post_types_supported )) {
				return;
			}

			$audio_location = get_post_meta( $post_id, 'amazon_polly_audio_location', true );
			$file           = 'amazon_polly_' . $post_id . '.mp3';
			$wp_filesystem  = $this->prepare_wp_filesystem();


			$file_handler = $this->get_file_handler();
			$file_handler->delete($wp_filesystem, $file, $post_id);
		} catch(Exception $e) {
			$this->show_error_notice("notice-error", "Amazon Polly - Error while deleting file!");
			error_log($e);
		}

	}

	private function skip_tags($text) {

		$skip_tags_array = $this->get_skiptags_array();

		foreach ($skip_tags_array as $value) {
			$text = preg_replace('/<' . $value . '>(\s*?)(.*?)(\s*?)<\/' . $value . '>/', '', $text);
		}

		return $text;
	}

	/**
	 * Encode SSML tags.
	 *
	 * @since  1.0.7
	 * @param  string $text text which should be encoded.
	 */
	private function encode_ssml_tags($text)
	{
		$text = preg_replace('/<ssml><break ([\S\s]*?)["\'](.*?)["\'](.*?)<\/ssml>/', '**AMAZONPOLLY*SSML*BREAK*$1***$2***SSML**', $text);
		return $text;
	}

	/**
	 * Checks if post title should be added.
	 *
	 * @since  1.0.7
	 */
	public function is_title_adder_enabled()
	{
		$value = get_option('amazon_polly_add_post_title', 'on');
		if (empty($value)) {
			$result = false;
		}
		else {
			$result = true;
		}

		return $result;
	}


	/**
	 * Add Amazon Pollu QuickTag button.
	 *
	 * @since    1.0.7
	 */
	public function add_quicktags() {

		$common = new AmazonAI_Common();
		$is_ssml_enabled = $common->is_ssml_enabled();

		if ( $is_ssml_enabled ) {
			if ( wp_script_is( 'quicktags' ) ) {
				?>
					<script type="text/javascript">
						QTags.addButton( 'eg_ssmlbreak', 'SSML Break', '<ssml><break time="1s"/></ssml>','','', 'Amazon Polly SSML Break Tag', 111 );
					</script>
				<?php
			}
		}
	}

  /**
	 * Configure supported HTML tags.
	 *
	 * @since  1.0.7
	 * @param  string $tags supported tags.
	 */
	public function allowed_tags_tinymce( $tags ) {
		$ssml_tags                       = array(
			'ssml',
			'speak',
			'break[time|whatever]',
			'emphasis[level]',
			'lang',
			'mark',
			'paragraph',
			'phoneme',
			'prosody',
			's',
			'say-as',
			'sub',
			'w',
			'amazon:breath',
			'amazon:auto-breaths',
			'amazon:effect[name]',
			'amazon:effect[phonation]',
			'amazon:effect[vocal-tract-length]',
			'amazon:effect[name]',
		);
		$tags['extended_valid_elements'] = implode( ',', $ssml_tags );
		return $tags;
	}

  /**
	 * Configure supported HTML tags.
	 *
	 * @since  1.0.7
	 * @param  string $tags supported tags.
	 */
	public function allowed_tags_kses( $tags ) {
		$tags['ssml']  = true;
		$tags['speak'] = true;
		$tags['break'] = array(
			'time' => true,
		);
		return $tags;
	}

	/**
	 * Return skip tags array.
	 *
	 * @since  1.0.7
	 */
	public function get_skiptags_array() {
		$array = get_option( 'amazon_ai_skip_tags' );
		$array = explode( ' ', $array );

		return $array;

	}

	/**
	 * Return post type value array.
	 *
	 * @since  1.0.7
	 */
	public function get_posttypes_array() {
		$posttypes_array = get_option( 'amazon_polly_posttypes', 'post' );
		$posttypes_array = explode( ' ', $posttypes_array );
		$posttypes_array = apply_filters( 'amazon_polly_post_types', $posttypes_array );

		return $posttypes_array;

	}

	/**
	 * Checks if checkbox should be checked for specific language.
	 *
	 * @since  2.0.0
	 */
	public function is_language_translable( $provided_language_code ) {

		foreach ($this->languages as $language_data) {
			$language_code = $language_data['code'];
			$is_language_supported = $language_data['transable'];

			if ( !empty($is_language_supported) ) {
				if ($provided_language_code === $language_code) {
					return $is_language_supported;
				}
			}
		}

		return false;
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'amazon-polly', plugin_dir_url( __FILE__ ) . 'css/amazonpolly-admin.css', array(), null, 'all' );
		wp_enqueue_style( 'jquery-ui-core' );
		wp_enqueue_style( 'jquery-ui-progressbar' );
		wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), '1.21.1', 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'amazon-polly', plugin_dir_url( __FILE__ ) . 'js/amazonpolly-admin.js', array( 'jquery' ), null, false );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		$nonce_array = array(
			'nonce' => wp_create_nonce( 'pollyajaxnonce' ),
		);
		wp_localize_script( 'jquery', 'pollyajax', $nonce_array );

	}

	public function is_translation_supported($source_language, $target_language) {

		if (( 'ko'== $source_language ) && ( 'he' == $target_language )) {
			return false;
		}

		return true;
	}

	/**
	 * Register meta box for 'Enable Amazon Polly' on post creation form.
	 *
	 * @since    1.0.0
	 */
	public function field_checkbox() {

		$post_types_supported = $this->get_posttypes_array();

		add_meta_box(
			'amazon_polly_box_id',
			// This is HTML id of the box on edit screen.
			'Amazon Polly',
			// Title of the box.
			'amazon_polly_box_content',
			// Function to be called to display the checkboxes, see the function below.
			$post_types_supported,
			// On which edit screen the box should appear.
			'normal',
			// Part of page where the box should appear.
			'high'
			// Priority of the box.
		);
	}


}
