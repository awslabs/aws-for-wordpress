<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 * @author     AWS Labs
 */
class Amazonpolly_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The options name to be used in this plugin
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string      $option_name    Option name of this plugin
	 */
	private $option_name = 'amazon_polly';

	/**
	 * The options name to be used for the audio location link metakey
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string      $option_name    Option name of this plugin
	 */
	private $audio_location_link_metakey = 'amazon_polly_audio_link_location';

	/**
	 * The options name to be used for the audio location metakey
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string      $option_name    Option name of this plugin
	 */
	private $audio_location_metakey = 'amazon_polly_audio_location';

	/**
	 * The options name to be used for the S3 bucket name metakey
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string      $option_name    Option name of this plugin
	 */
	private $s3_bucket_metakey = 'amazon_polly_s3_bucket';

	/**
	 * The polly client
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     Object      $polly_client   Client for polly interactions
	 */
	private $polly_client;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}


	/**
	 * Register meta box for 'Enable Amazon Polly' on post creation form.
	 *
	 * @since    1.0.0
	 */
	public function amazon_polly_field_checkbox() {

		$post_types_supported = apply_filters( 'amazon_polly_post_types', array( 'post' ) );

		add_meta_box(
			'amazon_polly_box_id',
			// This is HTML id of the box on edit screen.
			'Enable Amazon Polly',
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

	/**
	 * Run when deleting a post.
	 *
	 * @param      string $post_id   ID of the post which is gonna to be deleted.
	 * @since    1.0.0
	 */
	public function amazon_polly_delete_post( $post_id ) {
		// Check if this isn't an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$this->delete_post_audio( $post_id );
	}

	/**
	 * Important. Run whenever new post is being created (or updated). The method executes Amazon Polly API to create audio file.
	 *
	 * @since    1.0.0
	 */
	public function amazon_polly_save_post() {
		// Check if this isn't an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['amazon-polly-post-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['amazon-polly-post-nonce'] ), 'amazon-polly' ) ) {

			$is_post_available = isset( $_POST['content'] );
			// Input var okay.
			$is_polly_enabled = isset( $_POST['amazon_polly_enable'] );
			// Input var okay.
			$is_post_id_available = isset( $_POST['post_ID'] );
			// Input var okay.
			$is_amazon_polly_voice_id_available = isset( $_POST['amazon_polly_voice_id'] );
			// Input var okay.
			$is_amazon_polly_sample_rate_available = isset( $_POST['amazon_polly_sample_rate'] );
			// Input var okay.
			$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

			if ( $is_post_id_available && $is_post_available && $is_polly_enabled && $is_key_valid && $is_amazon_polly_voice_id_available && $is_amazon_polly_sample_rate_available ) {

				wp_nonce_field( 'amazon_polly', 'amazon_polly_sample_rate' );

				$post_id = absint( wp_unslash( $_POST['post_ID'] ) );
				// Input var okay.
				$voice_id = sanitize_text_field( wp_unslash( $_POST['amazon_polly_voice_id'] ) );
				// Input var okay.
				$sample_rate = sanitize_text_field( wp_unslash( $_POST['amazon_polly_sample_rate'] ) );
				// Input var okay.
				$sentences     = $this->prepare_post_text( $post_id );
				$wp_filesystem = $this->prepare_wp_filesystem();
				$this->convert_to_audio( $post_id, $sample_rate, $voice_id, $sentences, $wp_filesystem );
			}

			if ( $is_post_id_available && ! $is_polly_enabled ) {
				$this->delete_post_audio( $_POST['post_ID'] );
				update_post_meta( $_POST['post_ID'], 'amazon_polly_enable', 0 );
				update_post_meta( $_POST['post_ID'], $this->audio_location_metakey, '' );
			}
		}//end if
	}

	/**
	 * Delets audio for specific post.
	 *
	 * @param string $post_id ID of the post for which audio should be deleted.
	 * @since 1.0.0
	 */
	public function delete_post_audio( $post_id ) {
		$audio_location = get_post_meta( $post_id, $this->audio_location_metakey, true );
		$file           = 'amazon_polly_' . $post_id . '.mp3';
		$year           = get_the_date( 'Y', $post_id );
		$month          = get_the_date( 'm', $post_id );

		// Deleting audio file stored on S3.
		if ( 's3' === $audio_location ) {
			$s3_bucket_name = get_option( $this->s3_bucket_metakey );

			$result = $this->s3_client->deleteObject(
				array(
					'Bucket' => $s3_bucket_name,
					'Key'    => $year . '/' . $month . '/' . $file,
				)
			);
		}

		// Deleting local stored audio file.
		if ( 'local' === $audio_location ) {
			$wp_filesystem  = $this->prepare_wp_filesystem();
			$upload_dir     = wp_upload_dir()['basedir'];
			$file_full_path = $upload_dir . '/' . $year . '/' . $month . '/' . $file;

			$wp_filesystem->delete( $file_full_path );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/amazonpolly-admin.css', array(), $this->version, 'all' );
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
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/amazonpolly-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		$nonce_array = array(
			'nonce' => wp_create_nonce( 'pollyajaxnonce' ),
		);
		wp_localize_script( 'jquery', 'pollyajax', $nonce_array );

	}

	/**
	 * Add an options page under the Settings submenu
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_add_options_page() {

		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Amazon Polly Settings', 'amazon-polly' ),
			__( 'Amazon Polly', 'amazon-polly' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'amazon_polly_display_options_page' )
		);

	}

	/**
	 * Adds options page for Amazon Polly.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_display_options_page() {
		include_once 'partials/amazonpolly-admin-display.php';
	}

	/**
	 * Register all settings for options page.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_register_settings() {
		add_settings_section(
			'amazon_polly_general',
			__( 'General', 'amazonpolly' ),
			array( $this, 'amazon_polly_general_cb' ),
			$this->plugin_name
		);

		add_settings_field(
			'amazon_polly_access_key',
			__( 'AWS access key:', 'amazonpolly' ),
			array( $this, 'amazon_polly_access_key_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( 'label_for' => 'amazon_polly_access_key' )
		);

		add_settings_field(
			'amazon_polly_secret_key',
			__( 'AWS secret key:', 'amazonpolly' ),
			array( $this, 'amazon_polly_secret_key_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( 'label_for' => 'amazon_polly_secret_key' )
		);

		add_settings_field(
			'amazon_polly_sample_rate',
			__( 'Sample rate:', 'amazonpolly' ),
			array( $this, 'amazon_polly_sample_rate_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( 'label_for' => 'amazon_polly_sample_rate' )
		);

		add_settings_field(
			'amazon_polly_voice_id',
			__( 'Voice name:', 'amazonpolly' ),
			array( $this, 'amazon_polly_voice_id_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( 'label_for' => 'amazon_polly_voice_id' )
		);

		add_settings_field(
			'amazon_polly_position',
			__( 'Player position:', 'amazonpolly' ),
			array( $this, 'amazon_polly_position_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( 'label_for' => 'amazon_polly_position' )
		);

		add_settings_field(
			'amazon_polly_defconf',
			__( 'New post default:', 'amazonpolly' ),
			array( $this, 'amazon_polly_defconf_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( '' => 'amazon_polly_defconf' )
		);

		add_settings_field(
			'amazon_polly_autoplay',
			__( 'Autoplay:', 'amazonpolly' ),
			array( $this, 'amazon_polly_autoplay_cb' ),
			$this->plugin_name,
			'amazon_polly_general',
			array( 'label_for' => 'amazon_polly_autoplay' )
		);

		add_settings_section(
			'amazon_polly_storage',
			__( 'Cloud storage', 'amazonpolly' ),
			array( $this, 'amazon_polly_storage_cb' ),
			$this->plugin_name
		);

		add_settings_field(
			'amazon_polly_s3',
			__( 'Store audio in Amazon S3:', 'amazonpolly' ),
			array( $this, 'amazon_polly_s3_cb' ),
			$this->plugin_name,
			'amazon_polly_storage',
			array( 'label_for' => 'amazon_polly_s3' )
		);

		add_settings_field(
			'amazon_polly_cloudfront',
			__( 'Amazon CloudFront (CDN) domain name:', 'amazonpolly' ),
			array( $this, 'amazon_polly_cloudfront_cb' ),
			$this->plugin_name,
			'amazon_polly_storage',
			array( 'label_for' => 'amazon_polly_cloudfront' )
		);

		add_settings_section(
			'amazon_polly_podcast',
			__( 'Amazon Pollycast', 'amazonpolly' ),
			array( $this, 'amazon_pollycast_cb' ),
			$this->plugin_name
		);

		add_settings_field(
			'amazon_polly_podcast_email',
			__( 'iTunes contact email:', 'amazonpolly' ),
			array( $this, 'amazon_polly_podcast_email_cb' ),
			$this->plugin_name,
			'amazon_polly_podcast',
			array( 'label_for' => 'amazon_polly_podcast_email' )
		);

		add_settings_field(
			'amazon_polly_podcast_category',
			__( 'iTunes category:', 'amazonpolly' ),
			array( $this, 'amazon_polly_podcast_category_cb' ),
			$this->plugin_name,
			'amazon_polly_podcast',
			array( 'label_for' => 'amazon_polly_podcast_category' )
		);

		add_settings_field(
			'amazon_polly_podcast_explicit',
			__( 'iTunes explicit content:', 'amazonpolly' ),
			array( $this, 'amazon_polly_podcast_explicit_cb' ),
			$this->plugin_name,
			'amazon_polly_podcast',
			array( 'label_for' => 'amazon_polly_podcast_explicit' )
		);

		add_settings_field(
			'amazon_polly_podcast_icon',
			__( 'iTunes image:', 'amazonpolly' ),
			array( $this, 'amazon_polly_podcast_icon_cb' ),
			$this->plugin_name,
			'amazon_polly_podcast',
			array( 'label_for' => 'amazon_polly_podcast_icon' )
		);

		add_settings_section(
			'amazon_polly_additional',
			__( 'Additional configuration', 'amazonpolly' ),
			array( $this, 'amazon_polly_additional_cb' ),
			$this->plugin_name
		);

		add_settings_field(
			'amazon_polly_update_all',
			__( 'Bulk update all posts:', 'amazonpolly' ),
			array( $this, 'amazon_polly_update_all_cb' ),
			$this->plugin_name,
			'amazon_polly_additional',
			array( 'label_for' => 'amazon_polly_update_all' )
		);

		if ( empty( get_option( 'amazon_polly_access_key' ) ) ) {

			// Set AWS SDK settings.
			$aws_sdk_config = array(
				'region'  => 'us-east-1',
				'version' => 'latest',
			);

		} else {

			// Set AWS SDK settings.
			$aws_sdk_config = array(
				'region'      => 'us-east-1',
				'version'     => 'latest',
				'credentials' => array(
					'key'    => get_option( 'amazon_polly_access_key' ),
					'secret' => get_option( 'amazon_polly_secret_key' ),
				),
			);

		}//end if

		// Create an SDK class to use config.
		$sdk = new Aws\Sdk( $aws_sdk_config );

		$this->s3_client = $sdk->createS3();
		$this->client    = $sdk->createPolly();

		register_setting( $this->plugin_name, 'amazon_polly_access_key', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_secret_key', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_sample_rate', 'intval' );
		register_setting( $this->plugin_name, 'amazon_polly_voice_id', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_position', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_defconf', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_autoplay', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_s3', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_cloudfront', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_podcast_email', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_podcast_category', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_podcast_explicit', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_settings_hash', 'strval' );

		$this->amazon_polly_validate_credentials();
		$this->prepare_s3_bucket();

	}

	/**
	 * Method creates (if it doesn't already exists) a S3 bucket
	 *
	 * @since       1.0.0
	 */
	private function prepare_s3_bucket() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {
			$s3_bucket_name    = get_option( $this->s3_bucket_metakey );
			$create_new_bucket = false;

			if ( empty( $s3_bucket_name ) ) {
				$create_new_bucket = true;
			} else {
				try {
					$result = $this->s3_client->headBucket(
						array(
							'Bucket' => $s3_bucket_name,
						)
					);

				} catch ( Aws\S3\Exception\S3Exception $e ) {
					$create_new_bucket = true;
				}
			}

			for ( $i = 0; $i <= 10; $i++ ) {
				if ( $create_new_bucket ) {
					try {
						$rand1 = wp_rand( 10000000000, 99999999999 );
						$rand2 = md5( microtime() );
						$name  = 'audio_for_wordpress_' . $rand1 . $rand2;
						$name  = substr( $name, 0, 60 );

						$result = $this->s3_client->createBucket( array( 'Bucket' => $name ) );
						update_option( $this->s3_bucket_metakey, $name );
						$create_new_bucket = false;
					} catch ( Aws\S3\Exception\S3Exception $e ) {
						update_option( $this->s3_bucket_metakey, '' );
					}
				}
			}
		}//end if
	}

	/**
	 * Method retrievies post which ID was provided, and then preapre content text (removing special characters etc.).
	 *
	 * @since       1.0.0
	 * @param       string $post_id     ID of the post for which test (content) should be prepapred for conversion.
	 */
	private function prepare_post_text( $post_id ) {

		$post_content = get_the_title( $post_id ) . '**AMAZONPOLLY*SSML*BREAKTIME*1s**';
		$post_content = $post_content . get_post_field( 'post_content', $post_id );
		$post_content = str_replace( '&nbsp;', ' ', $post_content );
		$post_content = strip_shortcodes( $post_content );
		$post_content = strip_tags( $post_content, '<break>' );
		$post_content = esc_html( $post_content );
		$post_content = str_replace( '&nbsp;', ' ', $post_content );

		$post_content_temp = '';
		$paragraphs        = explode( "\n", $post_content );
		foreach ( $paragraphs as $paragraph ) {
			$paragraph_size = strlen( trim( $paragraph ) );
			if ( $paragraph_size > 0 ) {
				$post_content_temp = $post_content_temp . "\n" . $paragraph;
			}
		}
		$post_content = $post_content_temp;

		$post_content = html_entity_decode( $post_content, ENT_QUOTES, 'UTF-8' );
		$post_content = str_replace( '&', ' and ', $post_content );
		$post_content = str_replace( '<', ' ', $post_content );
		$post_content = str_replace( '>', ' ', $post_content );
		$post_content = str_replace( '**AMAZONPOLLY*SSML*BREAKTIME*1s**', '<break time="1s"/>', $post_content );
		$parts        = [];

		if ( ! empty( $post_content ) ) {
			$part_id    = 0;
			$paragraphs = explode( "\n", $post_content );

			foreach ( $paragraphs as $paragraph ) {
				$paragraph_size = strlen( trim( $paragraph ) );
				if ( $paragraph_size > 0 ) {

					if ( $paragraph_size <= 1450 ) {
						$parts[ $part_id ] = $paragraph . '<break time="500ms"/>';
						$part_id++;
					} else {

						$words        = explode( ' ', $paragraph );
						$current_part = '';
						$last_part    = '';

						foreach ( $words as $word ) {
							$word_length         = strlen( $word );
							$current_part_length = strlen( $current_part );
							if ( $word_length + $current_part_length < 1400 ) {
								$current_part = $current_part . $word . ' ';
								$last_part    = $current_part;
							} else {
								$current_part      = $current_part . $word . ' ';
								$parts[ $part_id ] = $current_part;
								$part_id++;
								$current_part = '';
								$last_part    = '';
							}
						}

						$parts[ $part_id ] = $last_part . '<break time="500ms"/>';
						$part_id++;

					}//end if
				}//end if
			}//end foreach
		}//end if

		return $parts;
	}

	/**
	 * Method execute Amazon Polly API and convert content which was provided to audio file.
	 *
	 * @param           string $post_id                 ID of the posts which is being converted.
	 * @param           string $sample_rate         Sample rate for speech conversion.
	 * @param           string $voice_id                Amazon Polly voice ID.
	 * @param           string $sentences               Sentences which should be converted to audio.
	 * @param           string $wp_filesystem       Reference to WP File system variable.
	 * @since           1.0.0
	 */
	private function convert_to_audio( $post_id, $sample_rate, $voice_id, $sentences, $wp_filesystem ) {

		$sample_rate_values = array( '22050', '16000', '8000' );
		if ( ! in_array( $sample_rate, $sample_rate_values, true ) ) {
			$sample_rate = '22050';
		}


		$upload_dir           = wp_upload_dir()['basedir'];
		$file_prefix          = '/amazon_polly_';
		$file_name            = $file_prefix . $post_id . '.mp3';
		$file_temp_full_name  = $upload_dir . $file_name;
		$dir_final_full_name  = $upload_dir . '/' . get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id );
		$file_final_full_name = $dir_final_full_name . $file_name;
		// Delete temporary file if already exists.
		if ( $wp_filesystem->exists( $file_temp_full_name ) ) {
			$wp_filesystem->delete( $file_temp_full_name );
		}
		// Delete final file if already exists.
		if ( $wp_filesystem->exists( $file_final_full_name ) ) {
			$wp_filesystem->delete( $file_final_full_name );
		}
		$first_part = true;
		foreach ( $sentences as $key => $text_content ) {
			$amazon_polly_mark_value = 'wp-plugin-awslabs';
			$amazon_polly_mark_value = apply_filters( 'amazon_polly_mark_value', $amazon_polly_mark_value );

			$ssml_text_content = '<speak><mark name="' . esc_attr( $amazon_polly_mark_value ) . '"/>' . $text_content . '</speak>';

			// Synthesize the text.
			$result = $this->client->synthesizeSpeech(
				array(
					'OutputFormat' => 'mp3',
					'SampleRate'   => $sample_rate,
					'Text'         => $ssml_text_content,
					'TextType'     => 'ssml',
					'VoiceId'      => $voice_id,
				)
			);
			// Grab the stream and output to a file.
			$contents = $result['AudioStream']->getContents();
			// Save first part of the audio stream in the parial temporary file.
			$wp_filesystem->put_contents( $file_temp_full_name . '_part_' . $key, $contents );
			// Merge new temporary file with previous ones.
			if ( $first_part ) {
				$wp_filesystem->put_contents( $file_temp_full_name, $contents );
				$first_part = false;
			} else {
				$this->remove_id3( $file_temp_full_name . '_part_' . $key );
				$merged_file = $wp_filesystem->get_contents( $file_temp_full_name ) . $wp_filesystem->get_contents( $file_temp_full_name . '_part_' . $key );
				$wp_filesystem->put_contents( $file_temp_full_name, $merged_file );
			}
			// Deleting partial audio file.
			$wp_filesystem->delete( $file_temp_full_name . '_part_' . $key );
		}//end foreach
		$store_in_s3 = get_option( 'amazon_polly_s3' );
		if ( empty( $store_in_s3 ) ) {
			$audio_location = 'local';

			// We are storing audio file on the WP server.
			// Moving file to it's final location and deleting temporary file.
			if ( ! $wp_filesystem->is_dir( $dir_final_full_name ) ) {
				wp_mkdir_p( $dir_final_full_name );
			}

			$wp_filesystem->move( $file_temp_full_name, $file_final_full_name, true );
			$wp_filesystem->delete( $file_temp_full_name );
			$audio_location_link = wp_upload_dir()['baseurl'] . '/' . get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . $file_name;
		} else {
			// We are storing audio file on Amazon S3.
			$s3_bucket_name = get_option( $this->s3_bucket_metakey );
			$audio_location = 's3';
			$result         = $this->s3_client->putObject(
				array(
					'ACL'        => 'public-read',
					'Bucket'     => $s3_bucket_name,
					'Key'        => get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . $file_name,
					'SourceFile' => $file_temp_full_name,
				)
			);
			$wp_filesystem->delete( $file_temp_full_name );
			$cloudfront_domain_name = get_option( 'amazon_polly_cloudfront' );
			if ( empty( $cloudfront_domain_name ) ) {
				$audio_location_link = 'https://s3.amazonaws.com/' . $s3_bucket_name . '/' . get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . $file_name;
			} else {
				$audio_location_link = 'https://' . $cloudfront_domain_name . '/' . get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . $file_name;
			}
		}//end if

		// This will bust the browser cache when a content revision is made.
		$audio_location_link = add_query_arg( 'version', time(), $audio_location_link );

		// We are using a hash of these values to improve the speed of queries.
		$amazon_polly_settings_hash = md5( $voice_id . $sample_rate . $audio_location );

		update_post_meta( $post_id, $this->audio_location_link_metakey, $audio_location_link );
		update_post_meta( $post_id, $this->audio_location_metakey, $audio_location );
		// Update post meta data.
		update_post_meta( $post_id, 'amazon_polly_enable', 1 );
		update_post_meta( $post_id, 'amazon_polly_voice_id', $voice_id );
		update_post_meta( $post_id, 'amazon_polly_sample_rate', $sample_rate );
		update_post_meta( $post_id, 'amazon_polly_settings_hash', $amazon_polly_settings_hash );

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

	/**
	 * Method prepare WP_Filesystem variable for interacting with local file system.
	 *
	 * @since    1.0.0
	 */
	private function prepare_wp_filesystem() {
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
	 * Render the text for the general section
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_general_cb() {
		$nonce = wp_create_nonce( 'amazon-polly' );
		echo '<input type="hidden" name="amazon-polly-admin" value="' . esc_attr( $nonce ) . '" />';
	}

	/**
	 * Render the Access Key input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_access_key_cb() {
		$access_key = get_option( 'amazon_polly_access_key' );
		echo '<input type="text" class="regular-text" name="amazon_polly_access_key" id="amazon_polly_access_key" value="' . esc_attr( $access_key ) . '"> ';
		echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';
	}

				/**
				 * Render the Secret Key input for this plugin
				 *
				 * @since  1.0.0
				 */
	public function amazon_polly_secret_key_cb() {
		$secret_key = get_option( 'amazon_polly_secret_key' );
		echo '<input type="password" class="regular-text" name="amazon_polly_secret_key" id="amazon_polly_secret_key" value="' . esc_attr( $secret_key ) . '"> ';
		echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';
	}

	/**
	 * Render the Default Configuration input.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_defconf_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {
			$selected_defconf = get_option( 'amazon_polly_defconf' );
			$defconf_values   = [ 'Amazon Polly enabled', 'Amazon Polly disabled' ];

			echo '<select name="amazon_polly_defconf" id="amazon_polly_defconf" >';
			foreach ( $defconf_values as $defconf ) {
				echo '<option value="' . esc_attr( $defconf ) . '" ';
				if ( strcmp( $selected_defconf, $defconf ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $defconf ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the Position input.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_position_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {
			$selected_position = get_option( 'amazon_polly_position' );
			$positions_values  = array( 'Before post', 'After post', 'Do not show' );

			echo '<select name="amazon_polly_position" id="amazon_polly_position" >';
			foreach ( $positions_values as $position ) {
				echo '<option value="' . esc_attr( $position ) . '" ';
				if ( strcmp( $selected_position, $position ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $position ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the text for the storage section
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_storage_cb() {
	}

	/**
	 * Render the text for the additional section
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_additional_cb() {
	}

	/**
	 * Render the 'store in S3' input.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_s3_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {

			$selected_s3    = get_option( 'amazon_polly_s3' );
			$s3_bucket_name = get_option( $this->s3_bucket_metakey );

			if ( empty( $s3_bucket_name ) ) {
				$checkbox_disabled = 'disabled';
			} else {
				$checkbox_disabled = '';
			}

			if ( empty( $selected_s3 ) ) {
				$checked                = ' ';
				$bucket_name_visibility = 'display:none';
			} else {
				$checked                = ' checked ';
				$bucket_name_visibility = ' ';
			}

			echo '<input type="checkbox" name="amazon_polly_s3" id="amazon_polly_s3" ' . esc_attr( $checked ) . ' ' . esc_attr( $checkbox_disabled ) . '> ';
			echo '<label for="amazon_polly_s3" id="amazon_polly_s3_bucket_name_box" style="' . esc_attr( $bucket_name_visibility ) . '"> Your S3 Bucket name is <b>' . esc_attr( $s3_bucket_name ) . '</b></label>';
			echo '<p class="description">Audio files are saved on and streamed from Amazon S3. Learn more <a target="_blank" href="https://aws.amazon.com/s3">https://aws.amazon.com/s3</a></p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}//end if
	}


	/**
	 * Render the 'use CloudFront' input.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_cloudfront_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {

			$cloudfront_domain_name = get_option( 'amazon_polly_cloudfront' );
			$s3_enabled             = get_option( 'amazon_polly_s3' );

			if ( empty( $s3_enabled ) ) {
				$disabled = ' disabled="disabled" ';
			} else {
				$disabled = ' ';
			}

			echo '<input type="text" name="amazon_polly_cloudfront" class="regular-text" "id="amazon_polly_cloudfront" value="' . esc_attr( $cloudfront_domain_name ) . '" ' . esc_attr( $disabled ) . '> ';
			echo '<p class="description">If you have set up CloudFront distribution for your S3 bucket, the name of the domain. For additional information and pricing, see: <a target="_blank" href="https://aws.amazon.com/cloudfront">https://aws.amazon.com/cloudfront</a> </p>';

		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the autoplay input.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_autoplay_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {

			$selected_autoplay = get_option( 'amazon_polly_autoplay' );

			if ( empty( $selected_autoplay ) ) {
				$checked = ' ';
			} else {
				$checked = ' checked ';
			}
			echo '<input type="checkbox" name="amazon_polly_autoplay" id="amazon_polly_autoplay" ' . esc_attr( $checked ) . '> ';
			echo '<label for="amazon_polly_autoplay">Automatically play audio content when page loads</label>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the Sample Rate input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_sample_rate_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {

			$sample_rate  = get_option( 'amazon_polly_sample_rate' );
			$sample_array = array( '22050', '16000', '8000' );

			echo '<select name="amazon_polly_sample_rate" id="amazon_polly_sample_rate" >';
			foreach ( $sample_array as $rate ) {
				echo '<option value="' . esc_attr( $rate ) . '" ';
				if ( strcmp( $sample_rate, $rate ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $rate ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Validate if AWS credentials are proper.
	 *
	 * @since  1.0.0
	 */
	private function amazon_polly_validate_credentials() {
		try {
			$voice_list = $this->client->describeVoices();
			update_option( 'amazon_polly_valid_keys', '1' );
		} catch ( Exception $e ) {
			update_option( 'amazon_polly_valid_keys', '0' );
		}
	}

	/**
	 * Render the Polly Voice input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_voice_id_cb() {

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

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {

			$voice_id   = get_option( 'amazon_polly_voice_id' );
			$voice_list = $this->client->describeVoices();

			echo '<select name="amazon_polly_voice_id" id="amazon_polly_voice_id">';
			usort( $voice_list['Voices'], 'sort_voices' );
			foreach ( $voice_list['Voices'] as $voice ) {
				echo '<option value="' . esc_attr( $voice['Id'] ) . '" ';
				if ( strcmp( $voice_id, $voice['Id'] ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $voice['LanguageName'] ) . ' - ' . esc_attr( $voice['Id'] ) . '</option>';
			}
			echo '</select>';
		} else {
			$voice_id = get_option( 'amazon_polly_voice_id' );
			echo '<input type="hidden" name="amazon_polly_voice_id" id="amazon_polly_voice_id" value="' . esc_attr( $voice_id ) . '">';
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}//end if

	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_update_all_cb() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {
			$message = $this->get_price_message_for_update_all();
			echo '<p>';
				echo '<button type="button" class="button" name="amazon_polly_update_all" id="amazon_polly_update_all">Bulk Update</button>';
				echo '<label id="label_amazon_polly_update_all" for="amazon_polly_update_all"> Changes must be saved before proceeding with a bulk update.</label>';
			echo '</p>';
			echo '<div id="amazon_polly_bulk_update_div">';
				echo '<p id="amazon_polly_update_all_pricing_message" class="description">' . esc_html( $message ) . '</p>';
				echo '<p><button type="button" class="button button-primary" id="amazon_polly_batch_transcribe" >Bulk Update</button></p>';
				echo '<div id="amazon-polly-progressbar"><div class="amazon-polly-progress-label">Loading...</div></div>';
			echo '</div>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Podcast section description.
	 *
	 * @since  1.0.0
	 */
	public function amazon_pollycast_cb() {
		echo '<p>Amazon Pollycast available at: <a target = "_blank" href="' . esc_attr( get_feed_link( 'amazon-pollycast' ) ) . '">' . esc_html( get_feed_link( 'amazon-pollycast' ) ) . '</a></p>';
		echo '<p>Submit your Amazon Pollycast to iTunes iConnect: <a target = "_blank" href="https://podcastsconnect.apple.com/">' . esc_html( 'https://podcastsconnect.apple.com/' ) . '</a></p>';
	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_podcast_email_cb() {
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {
			$selected_email = get_option( 'amazon_polly_podcast_email' );
			echo '<input class="regular-text" name="amazon_polly_podcast_email" id="amazon_polly_podcast_email" value="' . esc_attr( $selected_email ) . '"/>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_podcast_category_cb() {
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		$default_category  = 'News & Politics';
		$select_categories = array(
			'Arts',
			'Business',
			'Comedy',
			'Education',
			'Games & Hobbies',
			'Government & Organizations',
			'Health',
			'Kids',
			'Music',
			'News & Politics',
			'Religion',
			'Science & Medicine',
			'Society & Culture',
			'Sports & Recreation',
			'Technology',
			'TV & Film',
		);

		if ( $is_key_valid ) {
			$selected_category = get_option( 'amazon_polly_podcast_category' );
			if ( ! $selected_category ) {
				$selected_category = 'News & Politics';
			}
			echo '<select name="amazon_polly_podcast_category" id="amazon_polly_podcast_category">';
			foreach ( $select_categories as $category ) {
				echo '<option value="' . esc_attr( $category ) . '" ';
				if ( strcmp( $selected_category, $category ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $category ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_podcast_explicit_cb() {
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		$select_explicits = array(
			'yes',
			'no',
		);

		if ( $is_key_valid ) {
			$selected_explicit = get_option( 'amazon_polly_podcast_explicit' );
			echo '<select name="amazon_polly_podcast_explicit" id="amazon_polly_podcast_explicit">';
			foreach ( $select_explicits as $explicit ) {
				echo '<option value="' . esc_attr( $explicit ) . '" ';
				if ( strcmp( $selected_explicit, $explicit ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( ucfirst( $explicit ) ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_podcast_icon_cb() {
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

		if ( $is_key_valid ) {
			$query['autofocus[section]'] = 'amazonpolly';
			$section_link                = add_query_arg( $query, admin_url( 'customize.php' ) );
			echo '<p>Upload a podcast icon using the <a target="_blank" href="' . esc_url( $section_link ) . '">Customizer</a>.</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Determine how many posts should be transcribed.
	 */
	public function get_num_posts_needing_transcription() {
		$post_types_supported        = apply_filters( 'amazon_polly_post_types', array( 'post' ) );
		$amazon_polly_voice_id       = get_option( 'amazon_polly_voice_id' );
		$amazon_polly_sample_rate    = get_option( 'amazon_polly_sample_rate' );
		$amazon_polly_audio_location = ( 'on' === get_option( 'amazon_polly_s3' ) ) ? 's3' : 'local';

		$args  = array(
			'posts_per_page' => '-1',
			'post_type'      => $post_types_supported,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => $this->audio_location_link_metakey,
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'amazon_polly_voice_id',
					'value'   => $amazon_polly_voice_id,
					'compare' => '=',
				),
				array(
					'key'     => 'amazon_polly_sample_rate',
					'value'   => $amazon_polly_sample_rate,
					'compare' => '=',
				),
				array(
					'key'     => 'amazon_polly_audio_location',
					'value'   => $amazon_polly_audio_location,
					'compare' => '=',
				),
			),
		);
		$query = new WP_Query( $args );
		return count( $query->posts );
	}

	/**
	 * Calculate the total price of converting all posts into audio.
	 *
	 * @since  1.0.0
	 */
	private function get_price_message_for_update_all() {
		$post_types_supported = apply_filters( 'amazon_polly_post_types', array( 'post' ) );
		$number_of_characters = 0;

		// Retrieving the number of characters in all posts.
		$paged = 0;

		do {
			$paged++;
			$wp_query = new WP_Query(
				array(
					'posts_per_page' => 1,
					'post_type'      => $post_types_supported,
					'fields'         => 'ids',
					'paged'          => $paged,
				)
			);

			$number_of_posts = $wp_query->max_num_pages;

			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				$post_id = get_the_ID();

				$post_sentences = $this->prepare_post_text( $post_id );
				if ( ! empty( $post_sentences ) ) {
					foreach ( $post_sentences as $sentence ) {
						$number_of_characters += strlen( $sentence );
					}
				}
			}
		} while ( $paged < $number_of_posts );

		$amazon_polly_price = 0.000004;
		$total_price        = $amazon_polly_price * $number_of_characters;

		$message = 'You are about to convert ' . number_format( $number_of_posts, 0, '.', ',' ) . ' pieces of text-based content, which totals approximately ' . number_format( $number_of_characters, 0, '.', ',' ) . ' characters. Based on the Amazon Polly pricing ($4 dollars per 1 million characters) it will cost you about $' . money_format( '%i', $total_price ) . ' to convert all of your content into to speech-based audio. Some or all of your costs might be covered by the Free Tier (conversion of 5 million characters per month for free, for the first 12 months, starting from the first request for speech). Learn more https://aws.amazon.com/polly/';

		return $message;
	}

	/**
	 * Batch process the post transcriptions.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_ajax_transcribe() {
		check_ajax_referer( 'pollyajaxnonce', 'nonce' );

		$batch_size                  = 1;
		$post_types_supported        = apply_filters( 'amazon_polly_post_types', array( 'post' ) );
		$amazon_polly_voice_id       = get_option( 'amazon_polly_voice_id' );
		$amazon_polly_sample_rate    = get_option( 'amazon_polly_sample_rate' );
		$amazon_polly_audio_location = ( 'on' === get_option( 'amazon_polly_s3' ) ) ? 's3' : 'local';

		// We are using a hash of these values to improve the speed of queries.
		$amazon_polly_settings_hash = md5( $amazon_polly_voice_id . $amazon_polly_sample_rate . $amazon_polly_audio_location );

		$args	= array(
			'posts_per_page' => $batch_size,
			'post_type'      => $post_types_supported,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => $this->audio_location_link_metakey,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'amazon_polly_voice_id',
					'value'   => $amazon_polly_voice_id,
					'compare' => '!=',
				),
				array(
					'key'     => 'amazon_polly_sample_rate',
					'value'   => $amazon_polly_sample_rate,
					'compare' => '!=',
				),
				array(
					'key'     => 'amazon_polly_audio_location',
					'value'   => $amazon_polly_audio_location,
					'compare' => '!=',
				),
			),
		);
		$query    = new WP_Query( $args );
		$post_ids = wp_list_pluck( $query->posts, 'ID' );

		if ( is_array( $post_ids ) && ! empty( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$sentences     = $this->prepare_post_text( $post_id );
				$wp_filesystem = $this->prepare_wp_filesystem();
				$this->convert_to_audio( $post_id, $amazon_polly_sample_rate, $amazon_polly_voice_id, $sentences, $wp_filesystem );
			}
		} else {
			$step = 'done';
		}

		$percentage = $this->get_percentage_complete();
		echo wp_json_encode(
			array(
				'step'       => $step,
				'percentage' => $percentage,
			)
		);
		wp_die();
	}

	/**
	 * Calculate the percentage complete.
	 *
	 * @since  1.0.0
	 */
	public function get_percentage_complete() {
		$total_posts               = 0;
		$post_types_supported      = apply_filters( 'amazon_polly_post_types', array( 'post' ) );
		$posts_needing_translation = $this->get_num_posts_needing_transcription();


		foreach ( $post_types_supported as $post_type ) {
			$post_type_count = wp_count_posts( $post_type )->publish;
			$total_posts    += $post_type_count;
		}

		if ( 0 >= $total_posts || 0 >= $posts_needing_translation ) {
			$percentage = 100;
		} else {
			$percentage = round( $posts_needing_translation / $total_posts * 100, 2 );
		}

		return $percentage;
	}

	/**
	 * Adds a Settings action link to the Plugins page.
	 *
	 * @since  1.0.0
	 * @param  string $links A list of plugin action links.
	 */
	public function plugin_add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=amazonpolly">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
