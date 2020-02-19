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
 * The admin-specific functionality of the plugin.f
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
	 * The list of countries which are supported for translate functionality.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      array    $translate_langs    List of languages.
	 */
	private $translate_langs = array( 'en', 'de', 'es', 'fr', 'pt' );

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
	 * The polly client
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     Object      $polly_client   Client for polly interactions
	 */
	private $polly_client;

	/**
	 * Amazon Translate client
	 *
	 * @since   2.0.0
	 * @access  private
	 * @var     Object      $translate_client   AWS Amazon Translate client.
	 */
	private $translate_client;

	/**
	 * Amazon S3 file handler.
	 *
	 * @since   2.2.0
	 * @access  private
	 * @var     Object      $s3_file_handler   handler for S3 storage
	 */
	private $s3_file_handler;

	/**
	 * Local file handler.
	 *
	 * @since   2.2.0
	 * @access  private
	 * @var     Object      $local_file_handler   handler for local storage
	 */
	private $local_file_handler;

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

		$this->s3_file_handler = new AmazonAI_S3FileHandler();
		$this->local_file_handler = new AmazonAI_LocalFileHandler();
		$this->translator = new AmazonAI_Translator();
	}




	/**
	 * Register meta box for 'Enable Amazon Polly' on post creation form.
	 *
	 * @since    1.0.0
	 */
	public function amazon_polly_field_checkbox() {

		$post_types_supported = $this->amazon_polly_get_posttypes_array();

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
	 * Add Amazon Pollu QuickTag button.
	 *
	 * @since    1.0.7
	 */
	public function amazon_polly_add_quicktags() {

		$is_ssml_enabled = $this->amazon_polly_is_ssml_enabled();

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
	 * Important. Run whenever new post is being created (or updated). The method executes Amazon Polly API to create audio file.
	 *
	 * @since    1.0.0
	 */
	public function amazon_polly_save_post( $post_id, $post, $updated ) {

		// Check if this isn't an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check to make sure this is not a new post creation.
		if ( ! $updated ) {
			return;
		}

		$is_quick_edit = isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce');
		$is_polly_nonce_ok = isset( $_POST['amazon-polly-post-nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['amazon-polly-post-nonce'] ), 'amazon-polly' );

		// Check if this post is saved in 'regular' way.
		if (  $is_polly_nonce_ok || $is_quick_edit ) {

			$is_post_available = isset( $_POST['content'] );
			// Input var okay.
			$is_polly_enabled = isset( $_POST['amazon_polly_enable'] );
			// Input var okay.
			$is_post_id_available = isset( $post_id );
			// Input var okay.
			$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );

			// If this is quick edit, we need to check other place to see if polly was already enabled for this post.
			if ( $is_post_id_available && $is_quick_edit ) {
				if ( 1 == get_post_meta( $post_id, 'amazon_polly_enable', true)) {
					$is_polly_enabled = true;
				}
			}

			if ( $is_post_id_available && $is_post_available && $is_polly_enabled && $is_key_valid ) {

				wp_nonce_field( 'amazon_polly', 'amazon_polly_sample_rate' );

				$voice_id      = get_option( 'amazon_polly_voice_id' );
				$sample_rate   = get_option( 'amazon_polly_sample_rate' );
				$clean_text    = $this->clean_text( $post_id );
				$sentences     = $this->break_text( $clean_text );
				$wp_filesystem = $this->prepare_wp_filesystem();
				$this->convert_to_audio( $post_id, $sample_rate, $voice_id, $sentences, $wp_filesystem, '' );

				$src_lang = $this->get_src_lang();
				update_post_meta( $post_id, 'amazon_polly_transcript_' . $src_lang, $clean_text );
				update_post_meta( $post_id, 'amazon_polly_transcript_source_lan', $src_lang );

			}

			if ( $is_post_id_available && ! $is_polly_enabled ) {
				$this->delete_post_audio( $post_id );
				update_post_meta( $post_id, 'amazon_polly_enable', 0 );
				update_post_meta( $post_id, 'amazon_polly_audio_location', '' );
			}
		}
	}


	/**
	 * Delets audio for specific post.
	 *
	 * @param string $post_id ID of the post for which audio should be deleted.
	 * @since 1.0.0
	 */
	public function delete_post_audio( $post_id ) {
		$audio_location = get_post_meta( $post_id, 'amazon_polly_audio_location', true );
		$file           = 'amazon_polly_' . $post_id . '.mp3';
		$wp_filesystem  = $this->prepare_wp_filesystem();

		// Deleting audio file.
		$fileHandler = $this->getFileHandler();
		$fileHandler->delete($wp_filesystem, $file, $this->translate_langs, $post_id);

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

		// ************************************************* *
		// ***************** GENERAL SECTION **************** *
		add_settings_section( 'amazon_polly_general', __( 'General', 'amazonpolly' ), array( $this, 'amazon_polly_general_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_access_key', __( 'AWS access key:', 'amazonpolly' ), array( $this, 'amazon_polly_access_key_cb' ), $this->plugin_name, 'amazon_polly_general', array( 'label_for' => 'amazon_polly_access_key' ) );
		add_settings_field( 'amazon_polly_secret_key', __( 'AWS secret key:', 'amazonpolly' ), array( $this, 'amazon_polly_secret_key_cb' ), $this->plugin_name, 'amazon_polly_general', array( 'label_for' => 'amazon_polly_secret_key' ) );
		add_settings_field( 'amazon_polly_region', __( 'AWS Region:', 'amazonpolly' ), array( $this, 'amazon_polly_region_cb' ), $this->plugin_name, 'amazon_polly_general', array( 'label_for' => 'amazon_polly_region' ) );

		// ************************************************* *
		// ***************** PLAYER SECTION **************** *
		add_settings_section( 'amazon_polly_playersettings', __( 'Player settings', 'amazonpolly' ), array( $this, 'amazon_polly_playersettings_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_position', __( 'Player position:', 'amazonpolly' ), array( $this, 'amazon_polly_position_cb' ), $this->plugin_name, 'amazon_polly_playersettings', array( 'label_for' => 'amazon_polly_position' ) );
		add_settings_field( 'amazon_polly_player_label', __( 'Player label:', 'amazonpolly' ), array( $this, 'amazon_polly_player_label_cb' ), $this->plugin_name, 'amazon_polly_playersettings', array( 'label_for' => 'amazon_polly_player_label' ) );
		add_settings_field( 'amazon_polly_defconf', __( 'New post default:', 'amazonpolly' ), array( $this, 'amazon_polly_defconf_cb' ), $this->plugin_name, 'amazon_polly_playersettings', array( '' => 'amazon_polly_defconf' ) );
		add_settings_field( 'amazon_polly_autoplay', __( 'Autoplay:', 'amazonpolly' ), array( $this, 'amazon_polly_autoplay_cb' ), $this->plugin_name, 'amazon_polly_playersettings', array( 'label_for' => 'amazon_polly_autoplay' ) );

		// ************************************************* *
		// ************* POLLY SETTINGS SECTION ************ *
		add_settings_section( 'amazon_polly_pollysettings', __( 'Amazon Polly settings', 'amazonpolly' ), array( $this, 'amazon_polly_pollysettings_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_sample_rate', __( 'Sample rate:', 'amazonpolly' ), array( $this, 'amazon_polly_sample_rate_cb' ), $this->plugin_name, 'amazon_polly_pollysettings', array( 'label_for' => 'amazon_polly_sample_rate' ) );
		add_settings_field( 'amazon_polly_voice_id', __( 'Voice name:', 'amazonpolly' ), array( $this, 'amazon_polly_voice_id_cb' ), $this->plugin_name, 'amazon_polly_pollysettings', array( 'label_for' => 'amazon_polly_voice_id' ) );
		add_settings_field( 'amazon_polly_auto_breaths', __( 'Automated breaths:', 'amazonpolly' ), array( $this, 'amazon_polly_auto_breaths_cb' ), $this->plugin_name, 'amazon_polly_pollysettings', array( 'label_for' => 'amazon_polly_auto_breaths_id' ) );
		add_settings_field( 'amazon_polly_ssml', __( 'Enable SSML support:', 'amazonpolly' ), array( $this, 'amazon_polly_ssml_cb' ), $this->plugin_name, 'amazon_polly_pollysettings', array( 'label_for' => 'amazon_polly_ssml' ) );
		add_settings_field( 'amazon_polly_lexicons', __( 'Lexicons:', 'amazonpolly' ), array( $this, 'amazon_polly_lexicons_cb' ), $this->plugin_name, 'amazon_polly_pollysettings', array( 'label_for' => 'amazon_polly_lexicons' ) );
		add_settings_field( 'amazon_polly_speed', __( 'Audio speed [%]:', 'amazonpolly' ), array( $this, 'amazon_polly_speed_cb' ), $this->plugin_name, 'amazon_polly_pollysettings', array( 'label_for' => 'amazon_polly_speed' ) );

		// ************************************************* *
		// ************** STORAGE SECTION ************** *
		add_settings_section( 'amazon_polly_storage', __( 'Cloud storage', 'amazonpolly' ), array( $this, 'amazon_polly_storage_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_s3', __( 'Store audio in Amazon S3:', 'amazonpolly' ), array( $this, 'amazon_polly_s3_cb' ), $this->plugin_name, 'amazon_polly_storage', array( 'label_for' => 'amazon_polly_s3' ) );
		add_settings_field( 'amazon_polly_cloudfront', __( 'Amazon CloudFront (CDN) domain name:', 'amazonpolly' ), array( $this, 'amazon_polly_cloudfront_cb' ), $this->plugin_name, 'amazon_polly_storage', array( 'label_for' => 'amazon_polly_cloudfront' ) );

		// ************************************************* *
		// ************** PODCAST SECTION ************** *
		add_settings_section( 'amazon_polly_podcast', __( 'Amazon Pollycast', 'amazonpolly' ), array( $this, 'amazon_pollycast_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_podcast_enabled', __( 'Pollycast enabled:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_enabled_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_enabled' ) );
		add_settings_field( 'amazon_polly_podcast_email', __( 'iTunes contact email:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_email_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_email' ) );
		add_settings_field( 'amazon_polly_podcast_category', __( 'iTunes category:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_category_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_category' ) );
		add_settings_field( 'amazon_polly_podcast_explicit', __( 'iTunes explicit content:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_explicit_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_explicit' ) );
		add_settings_field( 'amazon_polly_podcast_icon', __( 'iTunes image:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_icon_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_icon' ) );
		add_settings_field( 'amazon_polly_podcast_feedsize', __( 'Feed size:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_feedsize_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_feedsize' ) );
		add_settings_field( 'amazon_polly_podcast_post_cat', __( 'Post categories:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_post_cat_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_post_cat' ) );
		add_settings_field( 'amazon_polly_podcast_https', __( 'Use HTTPS for audio files:', 'amazonpolly' ), array( $this, 'amazon_polly_podcast_https_cb' ), $this->plugin_name, 'amazon_polly_podcast', array( 'label_for' => 'amazon_polly_podcast_https' ) );

		// ************************************************* *
		// ************** ADDITIONAL SECTION ************** *
		add_settings_section( 'amazon_polly_additional', __( 'Additional configuration', 'amazonpolly' ), array( $this, 'amazon_polly_additional_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_update_all', __( 'Bulk update all posts:', 'amazonpolly' ), array( $this, 'amazon_polly_update_all_cb' ), $this->plugin_name, 'amazon_polly_additional', array( 'label_for' => 'amazon_polly_update_all' ) );
		add_settings_field( 'amazon_polly_add_post_title', __( 'Add post title to audio:', 'amazonpolly' ), array( $this, 'amazon_polly_add_post_title_cb' ), $this->plugin_name, 'amazon_polly_additional', array( 'label_for' => 'amazon_polly_add_post_title' ) );
		add_settings_field( 'amazon_polly_add_post_excerpt', __( 'Add post excerpt to audio:', 'amazonpolly' ), array( $this, 'amazon_polly_add_post_excerpt_cb' ), $this->plugin_name, 'amazon_polly_additional', array( 'label_for' => 'amazon_polly_add_post_excerpt' ) );
		add_settings_field( 'amazon_polly_posttypes', __( 'Post types:', 'amazonpolly' ), array( $this, 'amazon_polly_posttypes_cb' ), $this->plugin_name, 'amazon_polly_additional', array( 'label_for' => 'amazon_polly_posttypes' ) );

		// ************************************************* *
		// ************** TRANSLATION SECTION ************** *
		add_settings_section( 'amazon_polly_trans', __( 'Amazon Translate configuration', 'amazonpolly' ), array( $this, 'amazon_polly_trans_cb' ), $this->plugin_name );
		add_settings_field( 'amazon_polly_trans_enabled', __( 'Enable translation support:', 'amazonpolly' ), array( $this, 'amazon_polly_trans_enabled_cb' ), $this->plugin_name, 'amazon_polly_trans', array( 'label_for' => 'amazon_polly_trans_enabled' ) );
		add_settings_field( 'amazon_polly_transcript_enabled', __( 'Show transcript:', 'amazonpolly' ), array( $this, 'amazon_polly_transcript_enabled_cb' ), $this->plugin_name, 'amazon_polly_trans', array( 'label_for' => 'amazon_polly_transcript_enabled' ) );
		add_settings_field( 'amazon_polly_trans_src_lang', __( 'Source language:', 'amazonpolly' ), array( $this, 'amazon_polly_trans_src_lang_cb' ), $this->plugin_name, 'amazon_polly_trans', array( 'label_for' => 'amazon_polly_trans_src_lang' ) );
		add_settings_field( 'amazon_polly_trans_langs_src_label', __( 'Source language label:', 'amazonpolly' ), array( $this, 'amazon_polly_trans_langs_src_label_cb' ), $this->plugin_name, 'amazon_polly_trans', array( 'label_for' => 'amazon_polly_trans_langs_src_label' ) );
		add_settings_field( 'amazon_polly_trans_langs_label', __( 'Translations label:', 'amazonpolly' ), array( $this, 'amazon_polly_trans_langs_label_cb' ), $this->plugin_name, 'amazon_polly_trans', array( 'label_for' => 'amazon_polly_trans_langs_label' ) );
		add_settings_field( 'amazon_polly_trans_langs', __( 'Target languages:', 'amazonpolly' ), array( $this, 'amazon_polly_trans_langs_cb' ), $this->plugin_name, 'amazon_polly_trans', array( 'label_for' => 'amazon_polly_trans_langs' ) );

		add_option( 'amazon_polly_trans_validated' );


		// ************************************************* *
		$selected_region = get_option( 'amazon_polly_region' );

		if ( empty( $selected_region ) ) {
			update_option( 'amazon_polly_region', 'us-east-1' );
			$selected_region = 'us-east-1';
		}

		if ( empty( get_option( 'amazon_polly_access_key' ) ) ) {

			// Set AWS SDK settings.
			$aws_sdk_config = array(
				'region'  => $selected_region,
				'version' => 'latest',
			);

		} else {

			// Set AWS SDK settings.
			$aws_sdk_config = array(
				'region'      => $selected_region,
				'version'     => 'latest',
				'credentials' => array(
					'key'    => get_option( 'amazon_polly_access_key' ),
					'secret' => get_option( 'amazon_polly_secret_key' ),
				),
			);

		}//end if

		// Create an SDK class to use config.
		$sdk = new Aws\Sdk( $aws_sdk_config );

		$this->s3_file_handler->set_s3_client( $sdk->createS3() );
		$this->translate_client = $sdk->createTranslate();
		$this->polly_client = $sdk->createPolly();

		register_setting( $this->plugin_name, 'amazon_polly_access_key', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_secret_key', 'strval' );
		register_setting( $this->plugin_name, 'amazon_polly_s3', 'strval' );

		if ( $this->amazon_polly_is_ok() ) {
			register_setting( $this->plugin_name, 'amazon_polly_podcast_enabled', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_cloudfront', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_region', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_sample_rate', 'intval' );
			register_setting( $this->plugin_name, 'amazon_polly_voice_id', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_ssml', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_auto_breaths', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_position', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_player_label', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_defconf', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_autoplay', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_speed', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_feedsize', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_email', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_category', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_explicit', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_post_cat', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_https', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_settings_hash', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_podcast_explicit', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_add_post_title', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_add_post_excerpt', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_posttypes', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_lexicons', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_trans', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_trans_enabled', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_transcript_enabled', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_trans_src_lang', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_trans_langs_src_label', 'strval' );
			register_setting( $this->plugin_name, 'amazon_polly_trans_langs_label', 'strval' );

			foreach ( $this->translate_langs as $lan ) {
				register_setting( $this->plugin_name, 'amazon_polly_trans_langs_' . $lan, 'strval' );
				register_setting( $this->plugin_name, 'amazon_polly_trans_langs_' . $lan . '_voice', 'strval' );
				register_setting( $this->plugin_name, 'amazon_polly_trans_langs_' . $lan . '_label', 'strval' );
			}
		}//end if

		$this->amazon_polly_validate_credentials();

		$is_s3_enabled = $this->amazon_polly_is_s3_enabled();
		if ( $this->amazon_polly_is_ok() ) {
			if ( $is_s3_enabled ) {
				$this->getFileHandler()->prepare_s3_bucket();
			}
		}

	}

	/**
	 * Method returns file handler which is reponsible for communicating with proper storage location.
	 *
	 * @since       2.0.3
	 */
	private function getFileHandler() {

		if ( $this->amazon_polly_is_s3_enabled() ) {
		  return $this->s3_file_handler;
		} else {
			return $this->local_file_handler;
		}

	}

	/**
	 * Method retrievies post which ID was provided, and clean it.
	 *
	 * @since       1.0.12
	 * @param       string $post_id     ID of the post for which test (content) should be prepapred for conversion.
	 */
	private function clean_text( $post_id ) {

		// Depending on the plugin configurations, post's title will be added to the audio.
		if ( $this->amazon_polly_is_title_adder_enabled() ) {
			$clean_text = get_the_title( $post_id ) . '. **AMAZONPOLLY*SSML*BREAK*time=***1s***SSML** ';
		} else {
			$clean_text = '';
		}

		// Depending on the plugin configurations, post's excerpt will be added to the audio.
		if ( $this->amazon_polly_is_excerpt_adder_enabled() ) {
			$my_excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $post_id));
			$clean_text = $clean_text . $my_excerpt . ' **AMAZONPOLLY*SSML*BREAK*time=***1s***SSML** ';
		}

		$clean_text = $clean_text . get_post_field( 'post_content', $post_id );
		$clean_text = apply_filters( 'amazon_polly_content', $clean_text );
		$clean_text = str_replace( '&nbsp;', ' ', $clean_text );
		$clean_text = do_shortcode( $clean_text );

		$is_ssml_enabled = $this->amazon_polly_is_ssml_enabled();
		if ( $is_ssml_enabled ) {
			$clean_text = $this->amazon_polly_encode_ssml_tags( $clean_text );
		}

		$clean_text = strip_tags( $clean_text, '<break>' );
		$clean_text = esc_html( $clean_text );
		$clean_text = str_replace( '&nbsp;', ' ', $clean_text );
		$clean_text = preg_replace("/https:\/\/([^\s]+)/", "", $clean_text);

		// $clean_text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-START-', '', $clean_text );
		// $clean_text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-END-', '', $clean_text );
		// $clean_text = preg_replace( '/-AMAZONPOLLY-ONLYWORDS-START-[\S\s]*?-AMAZONPOLLY-ONLYWORDS-END-/', '', $clean_text );
		$clean_text_temp = '';
		$paragraphs      = explode( "\n", $clean_text );
		foreach ( $paragraphs as $paragraph ) {
			$paragraph_size = strlen( trim( $paragraph ) );
			if ( $paragraph_size > 0 ) {
				$clean_text_temp = $clean_text_temp . "\n" . $paragraph;
			}
		}
		$clean_text = $clean_text_temp;

		$clean_text = html_entity_decode( $clean_text, ENT_QUOTES, 'UTF-8' );
		$clean_text = str_replace( '&', ' and ', $clean_text );
		$clean_text = str_replace( '<', ' ', $clean_text );
		$clean_text = str_replace( '>', ' ', $clean_text );

		return $clean_text;

	}

	/**
	 * Method breakes input text into smaller parts.
	 *
	 * @since       1.0.0
	 * @param       string $text     Text which should be broken.
	 */
	private function break_text( $text ) {

		$text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-START-', '', $text );
		$text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-END-', '', $text );
		$text = preg_replace( '/-AMAZONPOLLY-ONLYWORDS-START-[\S\s]*?-AMAZONPOLLY-ONLYWORDS-END-/', '', $text );

		$parts = [];

		if ( ! empty( $text ) ) {
			$part_id    = 0;
			$paragraphs = explode( "\n", $text );

			foreach ( $paragraphs as $paragraph ) {
				$paragraph_size = strlen( trim( $paragraph ) );
				if ( $paragraph_size > 0 ) {

					if ( $paragraph_size <= 2800 ) {
						$parts[ $part_id ] = $paragraph . ' **AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML** ';
						$part_id++;
					} else {

						$words        = explode( ' ', $paragraph );
						$current_part = '';
						$last_part    = '';

						foreach ( $words as $word ) {
							$word_length         = strlen( $word );
							$current_part_length = strlen( $current_part );
							if ( $word_length + $current_part_length < 2800 ) {
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

						$parts[ $part_id ] = $last_part . ' **AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML** ';
						$part_id++;

					}//end if
				}//end if
			}//end foreach
		}//end if

		// Modify speed
		$parts = $this->modify_speed( $parts );

		return $parts;
	}


	/**
	 * Method update sentences (input of the method), and modify their speed,
	 * by adding SSML prosody tag for each sentence.
	 *
	 * @param           string $sentences                 Sentences which should be updated.
	 * @since           1.0.5
	 */
	private function modify_speed( $sentences ) {

		$new_sentences   = [];
		$new_sentence_id = 0;

		$speed = $this->amazon_polly_get_speed();

		if ( 100 !== $speed ) {
			foreach ( $sentences as $sentence ) {
				$new_sentence                      = '<prosody rate="' . $speed . '%">' . $sentence . '</prosody>';
				$new_sentences[ $new_sentence_id ] = $new_sentence;
				$new_sentence_id++;
			}
		}

		return $new_sentences;
	}



	/**
	 * Method execute Amazon Polly API and convert content which was provided to audio file.
	 *
	 * @param           string $post_id                 ID of the posts which is being converted.
	 * @param           string $sample_rate         Sample rate for speech conversion.
	 * @param           string $voice_id                Amazon Polly voice ID.
	 * @param           string $sentences               Sentences which should be converted to audio.
	 * @param           string $wp_filesystem       Reference to WP File system variable.
	 * @param           string $lang       Language
	 * @since           1.0.0
	 */
	private function convert_to_audio( $post_id, $sample_rate, $voice_id, $sentences, $wp_filesystem, $lang ) {

		foreach ( $this->translate_langs as $lan ) {
			if ( $lan == $lang ) {
				$voice_id = get_option( 'amazon_polly_trans_langs_' . $lan . '_voice' );
			}
		}

		$sample_rate_values = array( '22050', '16000', '8000' );
		if ( ! in_array( $sample_rate, $sample_rate_values, true ) ) {
			$sample_rate = '22050';
		}

		$upload_dir           = wp_upload_dir()['basedir'];
		$file_prefix          = 'amazon_polly_';
		$file_name            = $file_prefix . $post_id . $lang . '.mp3';
		$file_temp_full_name  = trailingslashit($upload_dir) . 'temp_' . $file_name;

		$dir_final_full_name  = trailingslashit($upload_dir);
		if ( get_option('uploads_use_yearmonth_folders') ) {
		   $dir_final_full_name .= get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . "/";
		}
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

			// Depending on the plugin configuration SSML tags for automated breaths sound will be added.
			$is_breaths_enabled = $this->amazon_polly_is_auto_breaths_enabled();
			if ( $is_breaths_enabled ) {
				$text_content = '<amazon:auto-breaths>' . $text_content . '</amazon:auto-breaths>';
			}

			// If plugin SSML support option is enabled, plugin will try to decode all SSML tags.
			$is_ssml_enabled = $this->amazon_polly_is_ssml_enabled();
			if ( $is_ssml_enabled ) {
				$text_content = $this->amazon_polly_decode_ssml_tags( $text_content );
			}
			$text_content = str_replace( '**AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML**', '<break time="500ms"/>', $text_content );
			$text_content = str_replace( '**AMAZONPOLLY*SSML*BREAK*time=***1s***SSML**', '<break time="500ms"/>', $text_content );

			$amazon_polly_mark_value = 'wp-plugin-awslabs';
			$amazon_polly_mark_value = apply_filters( 'amazon_polly_mark_value', $amazon_polly_mark_value );

			$ssml_text_content = '<speak><mark name="' . esc_attr( $amazon_polly_mark_value ) . '"/>' . $text_content . '</speak>';

			$lexicons       = $this->get_lexicons();
			$lexicons_array = explode( ' ', $lexicons );

			if ( ! empty( $lexicons ) and ( count( $lexicons_array ) > 0 ) ) {

				$result = $this->polly_client->synthesizeSpeech(
					array(
						'OutputFormat' => 'mp3',
						'SampleRate'   => $sample_rate,
						'Text'         => $ssml_text_content,
						'TextType'     => 'ssml',
						'VoiceId'      => $voice_id,
						'LexiconNames' => $lexicons_array,
					)
				);
			} else {

				$result = $this->polly_client->synthesizeSpeech(
					array(
						'OutputFormat' => 'mp3',
						'SampleRate'   => $sample_rate,
						'Text'         => $ssml_text_content,
						'TextType'     => 'ssml',
						'VoiceId'      => $voice_id,
					)
				);
			}//end if

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

		// Saving audio file in final destination.
		$fileHandler = $this->getFileHandler();
		$audio_location_link = $fileHandler->save($wp_filesystem, $file_temp_full_name, $dir_final_full_name, $file_final_full_name, $post_id, $file_name);

		// This will bust the browser cache when a content revision is made.
		$audio_location_link = add_query_arg( 'version', time(), $audio_location_link );

		// We are using a hash of these values to improve the speed of queries.
		$amazon_polly_settings_hash = md5( $voice_id . $sample_rate . $fileHandler->get_type() );

		if ( $lang == '' ) {
			update_post_meta( $post_id, 'amazon_polly_audio_link_location', $audio_location_link );
			update_post_meta( $post_id, 'amazon_polly_audio_location', $fileHandler->get_type() );
		} else {
			update_post_meta( $post_id, 'amazon_polly_translation_' . $lang, '1' );
		}

		// Update post meta data.
		update_post_meta( $post_id, 'amazon_polly_enable', 1 );
		update_post_meta( $post_id, 'amazon_polly_voice_id', get_option( 'amazon_polly_voice_id' ) );
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
		echo '<input type="text" class="regular-text" name="amazon_polly_access_key" id="amazon_polly_access_key" value="' . esc_attr( $access_key ) . '" autocomplete="off"> ';
		echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';
	}

	/**
	 * Render the Secret Key input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_secret_key_cb() {
		$secret_key = get_option( 'amazon_polly_secret_key' );
		echo '<input type="password" class="regular-text" name="amazon_polly_secret_key" id="amazon_polly_secret_key" value="' . esc_attr( $secret_key ) . '" autocomplete="off"> ';
		echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';
	}

	/**
	 * Render the Automated Breath input.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_auto_breaths_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			echo '<input type="checkbox" name="amazon_polly_auto_breaths" id="amazon_polly_auto_breaths" ' . $this->amazon_polly_checked_validator( 'amazon_polly_auto_breaths' ) . '> ';
			echo '<p class="description" for="amazon_polly_auto_breaths">If enabled, Amazon Polly automatically creates breathing noises at appropriate intervals</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the enable Source language label input.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_trans_langs_src_label_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_translation_enabled() ) {
				$value = $this->replace_if_empty( get_option( 'amazon_polly_trans_langs_src_label' ), 'English' );
				echo '<input type="text" class="regular-text" name="amazon_polly_trans_langs_src_label" id="amazon_polly_trans_langs_src_label" value="' . esc_attr( $value ) . '"> ';
			} else {
				echo '<p class="description">Amazon Translate needs to be enabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the enable Translation input.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_trans_langs_label_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_translation_enabled() ) {
				$trans_label = $this->replace_if_empty( get_option( 'amazon_polly_trans_langs_label' ), 'Listen in other languages: ' );
				echo '<input type="text" class="regular-text" name="amazon_polly_trans_langs_label" id="amazon_polly_trans_langs_label" value="' . esc_attr( $trans_label ) . '"> ';
			} else {
				echo '<p class="description">Amazon Translate needs to be enabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the input which will be responsible for providing information if
	 * transcript of the content in other languages should be visible.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_transcript_enabled_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_translation_enabled() ) {
				echo '<input type="checkbox" name="amazon_polly_transcript_enabled" id="amazon_polly_transcript_enabled" ' . $this->amazon_polly_checked_validator( 'amazon_polly_transcript_enabled' ) . ' > ';
			} else {
				echo '<p class="description">Amazon Translate needs to be enabled</p>';
			}
		} else {
				echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Utility function which checks if checkbox for option input should be checked.
	 *
	 * @param       string $option Name of the option which should be checked.
	 * @since  2.0.0
	 */
	private function amazon_polly_checked_validator( $option ) {
		$option_value = get_option( $option, 'on' );
		if ( empty( $option_value ) ) {
			return '';
		} else {
			return ' checked ';
		}

	}

	/**
	 * Render the enable Translation input.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_trans_enabled_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_s3_enabled() ) {

				$start_value = $this->amazon_polly_checked_validator( 'amazon_polly_trans_enabled' );
				$translate_accessible = $this->translator->is_translate_accessible($this->translate_client);
				$supported_regions = array( 'us-east-1', 'us-east-2', 'us-west-2', 'eu-west-1' );
				$selected_region   = get_option( 'amazon_polly_region', '' );

				if ( in_array( $selected_region, $supported_regions ) ) {
					echo '<input type="checkbox" name="amazon_polly_trans_enabled" id="amazon_polly_trans_enabled" ' . $this->amazon_polly_checked_validator( 'amazon_polly_trans_enabled' ) . '> ';
					if ( 'checked' == trim($start_value)) {
						if ( !$translate_accessible ) {
							echo '<p class="description"><b>Amazon Translate not accessible. You need to update your IAM policy.</b></p>';
						}
					}

				} else {
					echo '<p class="description">You need to use one of following regions: N.Virginia, Ohio, Oregon, Ireland</p>';
					update_option( 'amazon_polly_trans_enabled', '' );
				}

			} else {
				echo '<p class="description">Amazon S3 Storage needs to be enabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the Podcast enabled input.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_podcast_enabled_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			echo '<input type="checkbox" name="amazon_polly_podcast_enabled" id="amazon_polly_podcast_enabled" ' . $this->amazon_polly_checked_validator( 'amazon_polly_podcast_enabled' ) . '> ';
			echo '<p class="description" for="amazon_polly_podcast_enabled">If enabled, Amazon Pollycast will be generated</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the HTTPS podcast  input.
	 *
	 * @since  4.0.5
	 */
	public function amazon_polly_podcast_https_cb() {

		if ( $this->amazon_polly_is_ok() ) {
		  if ( $this->amazon_polly_is_podcast_enabled() ) {
		    echo '<input type="checkbox" name="amazon_polly_podcast_https" id="amazon_polly_podcast_https" ' . $this->amazon_polly_checked_validator( 'amazon_polly_podcast_https' ) . '> ';
		  } else {
		    echo '<p class="description">Amazon Pollycast is disabled</p>';
		  }
		} else {
		  echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the Add post title to audio input.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_add_post_title_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			echo '<input type="checkbox" name="amazon_polly_add_post_title" id="amazon_polly_add_post_title" ' . $this->amazon_polly_checked_validator( 'amazon_polly_add_post_title' ) . '> ';
			echo '<p class="description" for="amazon_polly_add_post_title">If enabled, each audio file will start from post title.</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the Add post excerpt to audio input.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_add_post_excerpt_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			echo '<input type="checkbox" name="amazon_polly_add_post_excerpt" id="amazon_polly_add_post_excerpt" ' . $this->amazon_polly_checked_validator( 'amazon_polly_add_post_excerpt' ) . '> ';
			echo '<p class="description" for="amazon_polly_add_post_excerpt">If enabled, each audio file will have post excerpt at the beginning.</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the Default Configuration input.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_defconf_cb() {

		if ( $this->amazon_polly_is_ok() ) {
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

		if ( $this->amazon_polly_is_ok() ) {
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
	 * Render the Player Label input.
	 *
	 * @since  1.0.12
	 */
	public function amazon_polly_lexicons_cb() {

		if ( $this->validate_credentials() ) {
			$lexicons = $this->get_lexicons();
			echo '<input type="text" class="regular-text" name="amazon_polly_lexicons" id="amazon_polly_lexicons" value="' . esc_attr( $lexicons ) . '"> ';
			echo '<p class="description" for="amazon_polly_lexicons">Specify lexicons names (seperated by space), which you have uploaded to your AWS account</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}


		/**
		 * Render the Player Label input.
		 *
		 * @since  1.0.3
		 */
	public function amazon_polly_player_label_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			$player_label = get_option( 'amazon_polly_player_label' );
			echo '<input type="text" class="regular-text" name="amazon_polly_player_label" id="amazon_polly_player_label" value="' . esc_attr( $player_label ) . '"> ';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the Post Type input box.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_posttypes_cb() {

		if ( $this->amazon_polly_is_ok() ) {

			$posttypes = $this->amazon_polly_get_posttypes();

			echo '<input type="text" class="regular-text" name="amazon_polly_posttypes" id="amazon_polly_posttypes" value="' . esc_attr( $posttypes ) . '"> ';
			echo '<p class="description" for="amazon_polly_posttypes">Post types in your WordPress environment</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}

	}

	/**
	 * Render the text for the Amazon Polly settings section
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_pollysettings_cb() {
	}

	/**
	 * Render the text for Player settings section
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_playersettings_cb() {
	}

	/**
	 * Render the text for the storage section
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_storage_cb() {
	}

	/**
	 * Render the text for the translation section
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_trans_cb() {
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

		if ( $this->amazon_polly_is_ok() ) {

			$selected_s3    = get_option( 'amazon_polly_s3' );
			$s3_bucket_name = $this->get_bucket_name();

			$message = '';

			if ( empty( $s3_bucket_name ) ) {
				$checkbox_disabled = '';
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

			echo '<input type="checkbox" name="amazon_polly_s3" id="amazon_polly_s3" ' . esc_attr( $checked ) . ' ' . esc_attr( $checkbox_disabled ) . '> <p class="description">' . esc_attr( $message ) . '</p>';

			$is_s3_enabled = $this->amazon_polly_is_s3_enabled();
			if ( $is_s3_enabled ) {
				echo '<label for="amazon_polly_s3" id="amazon_polly_s3_bucket_name_box" style="' . esc_attr( $bucket_name_visibility ) . '"> Your S3 Bucket name is <b>' . esc_attr( $s3_bucket_name ) . '</b></label>';
			}

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

		if ( $this->amazon_polly_is_ok() ) {
			$is_s3_enabled = $this->amazon_polly_is_s3_enabled();
			if ( $is_s3_enabled ) {

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
				echo '<p class="description">Amazon S3 Storage needs to be enabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}//end if

	}


	/**
	 * Render the enable SSML input.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_ssml_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			$is_s3_enabled = $this->amazon_polly_is_s3_enabled();
			if ( $is_s3_enabled ) {
				$is_ssml_enabled = $this->amazon_polly_is_ssml_enabled();

				if ( $is_ssml_enabled ) {
					$checked = ' checked ';
				} else {
					$checked = ' ';
				}

				echo '<input type="checkbox" name="amazon_polly_ssml" id="amazon_polly_ssml" ' . esc_attr( $checked ) . '> ';
			} else {
				echo '<p class="description">Amazon S3 Storage needs to be enabled</p>';
			}
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

		if ( $this->amazon_polly_is_ok() ) {

			$selected_autoplay = get_option( 'amazon_polly_autoplay' );

			if ( empty( $selected_autoplay ) ) {
				$checked = ' ';
			} else {
				$checked = ' checked ';
			}
			echo '<input type="checkbox" name="amazon_polly_autoplay" id="amazon_polly_autoplay" ' . esc_attr( $checked ) . '> ';
			echo '<p class="description" for="amazon_polly_autoplay">Automatically play audio content when page loads</p>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the autoplay input.
	 *
	 * @since  1.0.5
	 */
	public function amazon_polly_speed_cb() {

		if ( $this->amazon_polly_is_ok() ) {
			$speed = $this->amazon_polly_get_speed();
			echo '<input type="number" name="amazon_polly_speed" id="amazon_polly_speed" value="' . esc_attr( $speed ) . '">';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Return post type value.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_get_posttypes() {
		$posttypes = get_option( 'amazon_polly_posttypes', 'post' );
		return $posttypes;
	}

	/**
	 * Return post type value array.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_get_posttypes_array() {
		$posttypes_array = get_option( 'amazon_polly_posttypes', 'post' );
		$posttypes_array = explode( ' ', $posttypes_array );
		$posttypes_array = apply_filters( 'amazon_polly_post_types', $posttypes_array );

		return $posttypes_array;

	}

	/**
	 * Check if AWS credentials are correct
	 *
	 * @since  1.0.7
	 */
	private function amazon_polly_is_ok() {

		$this->amazon_polly_validate_credentials();
		$is_key_valid = ( get_option( 'amazon_polly_valid_keys' ) === '1' );
		if ( $is_key_valid ) {
			return true;
		} else {
			update_option( 'amazon_polly_trans_validated', '' );
			return false;
		}

	}

	/**
	 * Check if Translation is enabled.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_is_translation_enabled() {

		$translation_enabled = get_option( 'amazon_polly_trans_enabled', '' );

		if ( empty( $translation_enabled ) ) {
			$result = false;
		} else {
			$result = true;
		}

		$is_s3_enabled = $this->amazon_polly_is_s3_enabled();
		if ( $is_s3_enabled ) {
			return $result;
		}

		return false;
	}

	/**
	 * Check if SSML is enabled.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_is_ssml_enabled() {
		$ssml_enabled = get_option( 'amazon_polly_ssml', 'on' );

		if ( empty( $ssml_enabled ) ) {
			$result = false;
		} else {
			$result = true;
		}

		$is_s3_enabled = $this->amazon_polly_is_s3_enabled();
		if ( $is_s3_enabled ) {
			return $result;
		}

		return false;
	}

	/**
	 * Return speed for audio files.
	 *
	 * @since  1.0.5
	 */
	public function amazon_polly_get_speed() {
		$speed = get_option( 'amazon_polly_speed' );

		if ( empty( $speed ) ) {
			$speed = '100';
		}

		if ( intval( $speed ) < 20 ) {
			$speed = '20';
		}

		if ( intval( $speed ) > 200 ) {
			$speed = '200';
		}

		update_option( 'amazon_polly_speed', $speed );

		return $speed;

	}

	/**
	 * Checks if pollycast is enabled.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_is_podcast_enabled() {
		$value = get_option( 'amazon_polly_podcast_enabled', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Checks if auto breaths are enabled.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_is_auto_breaths_enabled() {
		$value = get_option( 'amazon_polly_auto_breaths', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Checks if post title should be added.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_is_title_adder_enabled() {
		$value = get_option( 'amazon_polly_add_post_title', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Checks if post title should be added.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_is_excerpt_adder_enabled() {
		$value = get_option( 'amazon_polly_add_post_excerpt', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Render the region input.
	 *
	 * @since  1.0.3
	 */
	public function amazon_polly_region_cb() {

		if ( $this->amazon_polly_is_ok() ) {

			$selected_region = get_option( 'amazon_polly_region' );

			$regions = array(
				'us-east-1'      => 'US East (N. Virginia)',
				'us-east-2'      => 'US East (Ohio)',
				'us-west-1'      => 'US West (N. California)',
				'us-west-2'      => 'US West (Oregon)',
				'eu-west-1'      => 'EU (Ireland)',
				'eu-west-2'      => 'EU (London)',
				'eu-west-3'      => 'EU (Paris)',
				'eu-central-1'   => 'EU (Frankfurt)',
				'ca-central-1'   => 'Canada (Central)',
				'sa-east-1'      => 'South America (Sao Paulo)',
				'ap-southeast-1' => 'Asia Pacific (Singapore)',
				'ap-northeast-1' => 'Asia Pacific (Tokyo)',
				'ap-southeast-2' => 'Asia Pacific (Sidney)',
				'ap-northeast-2' => 'Asia Pacific (Seoul)',
				'ap-south-1'     => 'Asia Pacific (Mumbai)',
			);

			echo '<select name="amazon_polly_region" id="amazon_polly_region" >';
			foreach ( $regions as $region_name => $region_label ) {
				echo '<option label="' . esc_attr( $region_label ) . '" value="' . esc_attr( $region_name ) . '" ';
				if ( strcmp( $selected_region, $region_name ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr__($region_label, $this->plugin_name) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}//end if

	}

	/**
	 * Render the translation target languages input.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_trans_langs_cb() {

		if ( $this->amazon_polly_is_ok() ) {

			if ( $this->amazon_polly_is_translation_enabled() ) {

				$src_lang   = $this->get_src_lang();
				$voice_list = $this->polly_client->describeVoices();
				usort( $voice_list['Voices'], 'sort_voices' );

				echo '<table>';
				$this->show_translate_option( $src_lang, $voice_list, 'en', 'English', 'English' );
				$this->show_translate_option( $src_lang, $voice_list, 'es', 'Spanish', 'Español' );
				$this->show_translate_option( $src_lang, $voice_list, 'de', 'German', 'Deutsch' );
				$this->show_translate_option( $src_lang, $voice_list, 'fr', 'French', 'Français' );
				$this->show_translate_option( $src_lang, $voice_list, 'pt', 'Portuguese', 'Português' );
				echo '</table>';

			} else {
				echo '<p class="description">Amazon Translate needs to be enabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}//end if

	}

	/**
	 * Render the translation target languages input.
	 *
	 * @param           string $language_label  Label which should be used for this language.
	 * @param           string $language_name   Name (in english) of this language.
	 * @param           string $lanuage Language code.
	 * @param           string $voice_list  List of available voices.
	 * @param           string $src_lang    Source Language (code).
	 * @since  2.0.0
	 */
	private function show_translate_option( $src_lang, $voice_list, $lanuage, $language_name, $language_label ) {

		if ( empty( $src_lang ) ) {
			$src_lang = 'en';
		}

		if ( $src_lang == $lanuage ) {
			return;
		}

		$lan_option       = 'amazon_polly_trans_langs_' . $lanuage;
		$lan_voice_option = 'amazon_polly_trans_langs_' . $lanuage . '_voice';
		$lan_label_option = 'amazon_polly_trans_langs_' . $lanuage . '_label';

		$disabled = '';
		if ( ( $src_lang == $lanuage ) or ( 'en' == $lanuage ) ) {
			$disabled = 'disabled';
		}

		echo '<tr>';
		echo '<td><input type="checkbox" name="' . $lan_option . '" id="' . $lan_option . '" ' . $this->check_if_lang_checked( $lan_option, $src_lang ) . ' ' . $disabled . '>' . $language_name . ' </td><td>';

		$voice_id = get_option( $lan_voice_option );
		echo '&emsp;&emsp;Voice: <select name="' . $lan_voice_option . '" id="' . $lan_voice_option . '">';
		foreach ( $voice_list['Voices'] as $voice ) {
			if ( strpos( $voice['LanguageName'], $language_name ) !== false ) {
				echo '<option value="' . esc_attr( $voice['Id'] ) . '" ';
				if ( strcmp( $voice_id, $voice['Id'] ) === 0 ) {
					echo 'selected="selected"';
				}
				echo '>' . esc_attr( $voice['LanguageName'] ) . ' - ' . esc_attr( $voice['Id'] ) . '</option>';
			}
		}
		echo '</select>';

		$lan_label = $this->replace_if_empty( get_option( $lan_label_option, $language_label ), $language_label );

		echo '</td><td>Label: <input type="text" class="regular-text" name="' . $lan_label_option . '" id="' . $lan_label_option . '" value="' . esc_attr( $lan_label ) . '" ></td> ';

		echo '</tr>';

	}

	private function replace_if_empty( $value, $new_value ) {
		if ( ! empty( $value ) ) {
			return $value;
		} else {
			return $new_value;
		}
	}

	/**
	 * Checks if checkbox should be checked for specific language.
	 *
	 * @since  2.0.0
	 */
	private function check_if_lang_checked( $lang_code ) {

		$value = get_option( $lang_code, '' );

		if ( 'amazon_polly_trans_langs_en' == $lang_code ) {
			return ' checked ';
		}

		if ( empty( $value ) ) {
			return '';
		} else {
			return ' checked ';
		}

	}

	/**
	 * Checks if checkbox should be checked for specific language.
	 *
	 * @since  2.0.0
	 */
	private function check_if_translate( $lang_code ) {

		foreach ( $this->translate_langs as $supported_lan ) {
			if ( $supported_lan == $lang_code ) {
				$value = get_option( 'amazon_polly_trans_langs_' . $supported_lan, '' );

				if ( 'en' == $lang_code ) {
					$value = 'on';
				}
			}
		}

		if ( empty( $value ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Render the translation source language input.
	 *
	 * @since  2.0.0
	 */
	public function amazon_polly_trans_src_lang_cb() {


		if ( $this->amazon_polly_is_ok() ) {
		  if ( $this->amazon_polly_is_translation_enabled() ) {

				$selected_src_lang = $this->get_src_lang();

				$langs = array(
					'en' => 'English',
					'de' => 'German',
					'es' => 'Spanish',
					'fr' => 'French',
					'pt' => 'Portuguese',
				);

				echo '<select name="amazon_polly_trans_src_lang" id="amazon_polly_trans_src_lang" >';
				foreach ( $langs as $lang_name => $lang_label ) {
					echo '<option label="' . esc_attr( $lang_label ) . '" value="' . esc_attr( $lang_name ) . '" ';
					if ( strcmp( $selected_src_lang, $lang_name ) === 0 ) {
						echo 'selected="selected"';
					}
					echo '>' . esc_attr__($lang_label, $this->plugin_name) . '</option>';
				}
				echo '</select>';

			} else {
		    echo '<p class="description">Amazon Translate needs to be enabled</p>';
		  }
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

		if ( $this->amazon_polly_is_ok() ) {

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
			$voice_list = $this->polly_client->describeVoices();
			update_option( 'amazon_polly_valid_keys', '1' );
		} catch ( Exception $e ) {
			update_option( 'amazon_polly_valid_keys', '0' );
		}
	}

	/**
	 * Validate if AWS credentials are proper.
	 *
	 * @since  1.0.0
	 */
	private function validate_credentials() {
		try {
			$voice_list = $this->polly_client->describeVoices();
			return true;
		} catch ( Exception $e ) {
			return false;
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

		if ( $this->amazon_polly_is_ok() ) {

			$voice_id   = get_option( 'amazon_polly_voice_id' );
			$voice_list = $this->polly_client->describeVoices();

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

		if ( $this->amazon_polly_is_ok() ) {
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
		if ( $this->amazon_polly_is_podcast_enabled() ) {
			echo '<p>Amazon Pollycast available at: <a target = "_blank" href="' . esc_attr( get_feed_link( 'amazon-pollycast' ) ) . '">' . esc_html( get_feed_link( 'amazon-pollycast' ) ) . '</a></p>';
			echo '<p>Submit your Amazon Pollycast to iTunes iConnect: <a target = "_blank" href="https://podcastsconnect.apple.com/">' . esc_html( 'https://podcastsconnect.apple.com/' ) . '</a></p>';
		}
	}

	/**
	 * Render UI for setting Amazon Pollycast feedsize
	 *
	 * @since  2.0.3
	 */
	public function amazon_polly_podcast_feedsize_cb() {
		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_podcast_enabled() ) {
				$value = get_option( 'amazon_polly_podcast_feedsize', 20 );
				echo '<input type="number" name="amazon_polly_podcast_feedsize" id="amazon_polly_podcast_feedsize" value="' . esc_attr( $value ) . '"/>';
			} else {
				echo '<p class="description">Amazon Pollycast is disabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}


	/**
	 * Render the possibility for specify category of posts which will be added in feed.
	 *
	 * @since  2.0.4
	 */
	public function amazon_polly_podcast_post_cat_cb() {
		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_podcast_enabled() ) {
				$value = get_option( 'amazon_polly_podcast_post_cat' );
				echo '<input class="regular-text" name="amazon_polly_podcast_post_cat" id="amazon_polly_podcast_post_cat" value="' . esc_attr( $value ) . '"/>';
			} else {
				echo '<p class="description">Amazon Pollycast is disabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_podcast_email_cb() {
		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_podcast_enabled() ) {
				$selected_email = get_option( 'amazon_polly_podcast_email' );
				echo '<input class="regular-text" name="amazon_polly_podcast_email" id="amazon_polly_podcast_email" value="' . esc_attr( $selected_email ) . '"/>';
			} else {
				echo '<p class="description">Amazon Pollycast is disabled</p>';
			}
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

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_podcast_enabled() ) {
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
				echo '<p class="description">Amazon Pollycast is disabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}//end if
	}

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_podcast_explicit_cb() {

		$select_explicits = array(
			'yes',
			'no',
		);

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_podcast_enabled() ) {
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
				echo '<p class="description">Amazon Pollycast is disabled</p>';
			}
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

		if ( $this->amazon_polly_is_ok() ) {
			if ( $this->amazon_polly_is_podcast_enabled() ) {
				$query['autofocus[section]'] = 'amazonpolly';
				$section_link                = add_query_arg( $query, admin_url( 'customize.php' ) );
				echo '<p>Upload a podcast icon using the <a target="_blank" href="' . esc_url( $section_link ) . '">Customizer</a>.</p>';
			} else {
				echo '<p class="description">Amazon Pollycast is disabled</p>';
			}
		} else {
			echo '<p>Please verify your AWS Credentials are accurate</p>';
		}
	}

	/**
	 * Checks how many posts should be converted.
	 *
	 * @since           1.0.0
	 */
	public function get_num_posts_needing_transcription() {
		$post_types_supported        = $this->amazon_polly_get_posttypes_array();
		$amazon_polly_voice_id       = get_option( 'amazon_polly_voice_id' );
		$amazon_polly_sample_rate    = get_option( 'amazon_polly_sample_rate' );
		$amazon_polly_audio_location = ( 'on' === get_option( 'amazon_polly_s3' ) ) ? 's3' : 'local';

		$args  = array(
			'posts_per_page' => '-1',
			'post_type'      => $post_types_supported,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'amazon_polly_audio_link_location',
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
	 * Method returns lexicons specified in plugin configuration.
	 *
	 * @since  1.0.12
	 */
	private function get_lexicons() {

		$lexicons = get_option( 'amazon_polly_lexicons', '' );
		$lexicons = trim( $lexicons );

		return $lexicons;

	}

	/**
	 * Calculate the total price of converting all posts into audio.
	 *
	 * @since  1.0.0
	 */
	private function get_price_message_for_update_all() {
		$post_types_supported = $this->amazon_polly_get_posttypes_array();
		$number_of_characters = 0;

		$posts_per_page  = apply_filters( 'amazon_polly_posts_per_page', 5 );
		$count_posts     = wp_count_posts()->publish;
		$max_count_posts = 100;

		// Retrieving the number of characters in all posts.
		$paged      = 0;
		$post_count = 0;

		do {
			$paged++;
			$wp_query = new WP_Query(
				array(
					'posts_per_page' => $posts_per_page,
					'post_type'      => $post_types_supported,
					'fields'         => 'ids',
					'paged'          => $paged,
				)
			);

			$number_of_posts = $wp_query->max_num_pages;

			while ( $wp_query->have_posts() ) {
				$post_count++;
				$wp_query->the_post();
				$post_id = get_the_ID();

				$clean_text     = $this->clean_text( $post_id );
				$post_sentences = $this->break_text( $clean_text );
				if ( ! empty( $post_sentences ) ) {
					foreach ( $post_sentences as $sentence ) {
						$sentence              = str_replace( '**AMAZONPOLLY*SSML*BREAK*time=***1s***SSML**', '', $sentence );
						$sentence              = str_replace( '**AMAZONPOLLY*SSML*BREAK*time=***500ms***SSML**', '', $sentence );
						$number_of_characters += strlen( $sentence );
					}
				}
			}

			// If we reached the number of posts which we wanted to read, we stop
			// reading next posts.
			if ( $post_count >= $max_count_posts ) {
				break;
			}
		} while ( $paged < $number_of_posts );

		// Price for converting single character according to Amazon Polly pricing.
		$amazon_polly_price = 0.000004;

		// Estimating average number of characters per post.
		if ( 0 !== $post_count ) {
			$post_chars_count_avg = $number_of_characters / $post_count;
		} else {
			$post_chars_count_avg = 0;
		}

		// Estimating the total price of convertion of all posts.
		$total_price = $amazon_polly_price * $count_posts * $post_chars_count_avg;

		$message = 'You are about to convert ' . number_format( $count_posts, 0, '.', ',' ) . ' pieces of text-based content, which totals approximately ' . number_format( $number_of_characters, 0, '.', ',' ) . ' characters. Based on the Amazon Polly pricing ($4 dollars per 1 million characters) it will cost you about $' . $total_price . ' to convert all of your content into to speech-based audio. Some or all of your costs might be covered by the Free Tier (conversion of 5 million characters per month for free, for the first 12 months, starting from the first request for speech). Learn more https://aws.amazon.com/polly/';

		return $message;
	}

	public function amazon_polly_ajax_translate() {

		check_ajax_referer( 'pollyajaxnonce', 'nonce' );

		$post_id = $_POST['post_id'];
		$phase   = $_POST['phase'];
		$langs   = $_POST['langs'];

		$step       = '';
		$percentage = 0;
		$message    = '';

		$all_langs = [];
		$index     = 0;
		$src_lang  = get_post_meta( $post_id, 'amazon_polly_transcript_source_lan', true );


		if ( empty( $src_lang ) ) {
			$message    = 'Amazon Translate functionality needs to be enabled before publishing the post';
			$step       = 'done';
			$percentage = 100;

		} else {
			foreach ( $this->translate_langs as $supported_lan ) {
				if ( $this->check_if_translate( $supported_lan ) and ( $supported_lan != $src_lang ) ) {
					$all_langs[ $index ] = $supported_lan;
					$index++;
				}
			}

			if ( 'start' == $phase ) {
				$langs = $all_langs;
			} else {

				if ( ( $key = array_search( 'en', $langs ) ) !== false ) {
					$language_code = 'en';
					unset( $langs[ $key ] );
				} else {
					$language_code = array_shift( $langs );
				}

				if ( 'en' == $language_code ) {
					$clean_text = $this->clean_text( $post_id );
				} else {
					$clean_text = get_post_meta( $post_id, 'amazon_polly_transcript_en', true );
					$src_lang   = 'en';
				}

				$wp_filesystem = $this->prepare_wp_filesystem();

				if ( $this->check_if_translate( $language_code ) and ( $language_code != $src_lang ) ) {
					$translated_text = $this->translator->translate( $this->translate_client, $clean_text, $src_lang,  $language_code);
					update_post_meta( $post_id, 'amazon_polly_transcript_' . $language_code, $translated_text );
					$sentences = $this->break_text( $translated_text );
					$this->convert_to_audio( $post_id, '', '', $sentences, $wp_filesystem, $language_code );
				}

				$percentage = 100 - ( count( $langs ) / $index ) * 100;
			}//end if

			if ( empty( $langs ) ) {
				$step    = 'done';
				$message = 'Translation completed!';
			}
		}//end if

		$next_langs = $langs;
		if ( ( 'en' != $src_lang ) && ( $key = array_search( 'en', $next_langs ) ) !== false ) {
			$next_lang = 'en';
		} else {
			$next_lang = array_shift( $next_langs );
		}

		if ( ! empty( $next_lang ) ) {
			$message = 'Translating from ' . $this->amazon_polly_map( $src_lang ) . ' to ' . $this->amazon_polly_map( $next_lang );
		} else {
			$message = 'Translation completed!';
		}

		if ( empty( $src_lang ) ) {
			$message = 'Amazon Translate functionality needs to be enabled before publishing the post';
		}

		echo wp_json_encode(
			array(
				'step'       => $step,
				'langs'      => $langs,
				'percentage' => $percentage,
				'message'    => $message,
			)
		);

		wp_die();
	}

	private function amazon_polly_map( $code ) {
		switch ( $code ) {
			case 'en':
				return 'English';
			case 'fr':
				return 'French';
			case 'de':
				return 'German';
			case 'pt':
				return 'Portuguese';
			case 'es':
				return 'Spanish';
		}

		return 'N/A';
	}

	/**
	 * Batch process the post transcriptions.
	 *
	 * @since  1.0.0
	 */
	public function amazon_polly_ajax_transcribe() {
		check_ajax_referer( 'pollyajaxnonce', 'nonce' );

		$batch_size                  = 1;
		$post_types_supported        = $this->amazon_polly_get_posttypes_array();
		$amazon_polly_voice_id       = get_option( 'amazon_polly_voice_id' );
		$amazon_polly_sample_rate    = get_option( 'amazon_polly_sample_rate' );
		$amazon_polly_audio_location = ( 'on' === get_option( 'amazon_polly_s3' ) ) ? 's3' : 'local';

		// We are using a hash of these values to improve the speed of queries.
		$amazon_polly_settings_hash = md5( $amazon_polly_voice_id . $amazon_polly_sample_rate . $amazon_polly_audio_location );

		$args     = array(
			'posts_per_page' => $batch_size,
			'post_type'      => $post_types_supported,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'amazon_polly_audio_link_location',
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
				$clean_text    = $this->clean_text( $post_id );
				$sentences     = $this->break_text( $clean_text );
				$wp_filesystem = $this->prepare_wp_filesystem();
				$this->convert_to_audio( $post_id, $amazon_polly_sample_rate, $amazon_polly_voice_id, $sentences, $wp_filesystem, '' );

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
		$post_types_supported      = $this->amazon_polly_get_posttypes_array();
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

	/**
	 * Get S3 bucket name. The method uses filter 'amazon_polly_s3_bucket_name,
	 * which allows to use customer S3 bucket name instead of default one.
	 *
	 * @since  1.0.6
	 */
	private function get_bucket_name() {

		$s3_bucket_name = get_option( 'amazon_polly_s3_bucket' );
		$s3_bucket_name = apply_filters( 'amazon_polly_s3_bucket_name', $s3_bucket_name );

		return $s3_bucket_name;
	}

	/**
	 * Configure supported HTML tags.
	 *
	 * @since  1.0.7
	 * @param  string $tags supported tags.
	 */
	public function amazon_polly_allowed_tags_kses( $tags ) {
		$tags['ssml']  = true;
		$tags['speak'] = true;
		$tags['break'] = array(
			'time' => true,
		);
		return $tags;
	}

	/**
	 * Configure supported HTML tags.
	 *
	 * @since  1.0.7
	 * @param  string $tags supported tags.
	 */
	public function amazon_polly_allowed_tags_tinymce( $tags ) {
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
	 * Returns source language.
	 *
	 * @since  2.0.0
	 */
	private function get_src_lang() {

		$value = get_option( 'amazon_polly_trans_src_lang', 'en' );

		if ( empty($value) ) {
			$value = 'en';
		}

		return $value;
	}

	/**
	 * Checks if auto breaths are enabled.
	 *
	 * @since  1.0.7
	 */
	public function amazon_polly_is_s3_enabled() {
		$value = get_option( 'amazon_polly_s3', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Encode SSML tags.
	 *
	 * @since  1.0.7
	 * @param  string $text text which should be encoded.
	 */
	private function amazon_polly_encode_ssml_tags( $text ) {

		$text = preg_replace( '/<ssml><break ([\S\s]*?)["\'](.*?)["\'](.*?)<\/ssml>/', '**AMAZONPOLLY*SSML*BREAK*$1***$2***SSML**', $text );

		return $text;
	}

	/**
	 * Encode SSML tags.
	 *
	 * @since  1.0.7
	 * @param  string $text text which should be decoded.
	 */
	private function amazon_polly_decode_ssml_tags( $text ) {

		$text = preg_replace( '/(\*\*AMAZONPOLLY\*SSML\*BREAK\*)(.*?)(\*\*\*)(.*?)(\*\*\*SSML\*\*)/', '<break $2"$4"/>', $text );

		return $text;
	}

}
