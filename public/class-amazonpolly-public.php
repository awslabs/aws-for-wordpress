<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/public
 * @author     AWS Labs
 */
class Amazonpolly_Public {

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
	 * @var AmazonAI_Common
	 */
	private $common;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 * @param AmazonAI_Common $common
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, AmazonAI_Common $common ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->common = $common;
	}

	public function title_filter( $title, $id = null ) {
		$common = $this->common;

			if ( is_singular() ) {

				// Check if Amazon Translate is enabled, if yes then...
				if ($common->is_translation_enabled() ) {
					if(isset($_GET['amazonai-language'])) {
						$selected_language = $_GET['amazonai-language'];
						$translated_title = get_post_meta( $id, 'amazon_polly_transcript_title_' . $selected_language , true );
						if ( ! empty( $translated_title ) ) {
							$title = $translated_title;
						}
					}
				}
			}


		return $title;
	}

	/**
	 * WordPress filter, responsible for adding public part of
	 * the plugin (audio player & tranlate part)
	 *
	 * @since    1.0.0
	 * @param      string $content       The content of the new post.
	 */
	public function content_filter( $content ) {

		// Really strange case
		if (!isset($GLOBALS)) {
			return $content;
		} else {
			if (!array_key_exists('post', $GLOBALS)) {
				return $content;
			}
		}


		$post_id = $GLOBALS['post']->ID;
		$common = $this->common;

		$source_language = $common->get_post_source_language($post_id);
		$polly_content = '';
		$translate_options = '';


		// Check if Amazon Polly is enabled in WP, if yes then...
		if ( $common->is_polly_enabled() ) {

			// Check if Amazon Polly is enabled for specific post.
			if ( get_post_meta( $post_id, 'amazon_polly_enable', true ) === '1' ) {

				$audio_location    = get_post_meta( $post_id, 'amazon_polly_audio_link_location', true );
				$selected_autoplay = get_option( 'amazon_polly_autoplay' );
				$player_label      = get_option( 'amazon_polly_player_label' );

				// Checks if this is single post view and if there is autoplay options selected.
				if ( is_singular() && ! empty( $selected_autoplay ) ) {
					$autoplay = 'autoplay';
				} else {
					$autoplay = '';
				}


				// Prepare "Power By" label.
				$voice_by_part = '';
				if ( $common->is_poweredby_enabled() ) {
					if ( is_singular() ) {
						$image  = __('<img src="https://d12ee1u74lotna.cloudfront.net/images/Voiced_by_Amazon_Polly_EN.png" width="100" alt="Voiced by Amazon Polly" >', $this->plugin_name);
					/**
					 * Filters the voiced by Polly image HTML
					 *
					 * @param string $image Voiced by Polly image HTML
					 * @param string $locale The current page locale
					 */
					$image  = apply_filters('amazon_polly_voiced_by_html', $image, get_locale());
            $voice_by_part = '<a href="https://aws.amazon.com/polly/" target="_blank" rel="noopener noreferrer">' . $image . '</a>';
					}
				}

				// Removing Amazon Polly special tags.
				$content = $content;
				$content = preg_replace( '/-AMAZONPOLLY-ONLYAUDIO-START-[\S\s]*?-AMAZONPOLLY-ONLYAUDIO-END-/', '', $content );
				$content = str_replace( '-AMAZONPOLLY-ONLYWORDS-START-', '', $content );
				$content = str_replace( '-AMAZONPOLLY-ONLYWORDS-END-', '', $content );

				// Create player area.
				if ( is_singular() ) {

					// By default we will show default player.
					$audio_part = $this->include_audio_player( 'src', $audio_location, $autoplay );

					// Checks if Translate functionaliy is turned on.
					if ($common->is_translation_enabled() ) {
						// Checks if other than default langue is choosen.
						if(isset($_GET['amazonai-language'])) {

							// Retrievie selected language.
							$selected_language = $_GET['amazonai-language'];

							if ( $source_language != $selected_language ) {

								$audio_part = '';
								foreach ($common->get_all_polly_languages() as $language_code) {
									if ($language_code === $selected_language) {
										$audio_part = $this->include_audio_player( $selected_language, $audio_location, $autoplay );
									}
								}
							}
						}
					}

					$subscribe_part = $this->get_subscribe_part();


					$polly_content = '
					<table id="amazon-polly-audio-table">
						<tr>
						<td id="amazon-polly-audio-tab">
							<div id="amazon-ai-player-label">' . $player_label . '</div>
							' . $audio_part . '
							<div id="amazon-polly-subscribe-tab">' . $subscribe_part . '</div>
							<div id="amazon-polly-by-tab">' . $voice_by_part . '</div>
						</td>
						</tr>
					</table>';
				}
			}
		}


		// Will create 'translate' options and content part. If enabled.
		if ( is_singular() ) {
			if ($common->is_translation_enabled() ) {
				$translate_options = $this->show_translations_options($post_id, $common);
				if(isset($_GET['amazonai-language'])) {
					$selected_language = $_GET['amazonai-language'];
					if ( $source_language != $selected_language ) {
						$content = get_post_meta( $post_id, 'amazon_polly_transcript_' . $selected_language , true );
					}
				}
			}
		}

		// Put plugin content in the correct position.
		$selected_position = get_option( 'amazon_polly_position' );
		if ( strcmp( $selected_position, 'Do not show' ) === 0 ) {
			$content = $content;
		} elseif ( strcmp( $selected_position, 'After post' ) === 0 ) {
			$content = $content . $translate_options . $polly_content;
		} else {
			$content = $translate_options . $polly_content . $content;
		}

		return $content;
	}



	function show_translations_options($post_id, $common) {

		$source_language = $common->get_post_source_language($post_id);
		$options = '<div class="amazon-ai-flags-container">';
		$options = $options . $this->show_flag_button($common, $source_language);

		$number_of_flags = 1;

		foreach ($common->get_all_translatable_languages() as $language_code) {
			$content = get_post_meta( $post_id, 'amazon_polly_transcript_' . $language_code , true );


			if ( ! empty( $content ) ) {
				if ( $source_language != $language_code ) {
					$number_of_flags = $number_of_flags + 1;
					$options .= $this->show_flag_button($common, $language_code);
				}
			}
		}

		if ( $number_of_flags > 1) {
			$options = $options . '</div>';
		} else {
			$options = '';
		}

		return $options;
	}

	function show_flag_button( $common, $language ) {

		$link = esc_url( add_query_arg( 'amazonai-language', $language ));

		$display = $common->get_language_display( $language );
		$translate_option_flag_button = '<div class="amazon-ai-flag"><a href="' . $link . '"><img src="https://d12ee1u74lotna.cloudfront.net/images/flags/' . $language . '.png" class="amazon-ai-flag-image" alt="' . $language . ' flag"></a></div>';
		$translate_option_label_button = '<div class="amazon-polly-trans-label"><a href="' . $link . '">' . $common->get_language_label($language) . '&emsp;</a></div>';

		if (strcmp("Flag", $display) === 0) {
			return $translate_option_flag_button;
		} elseif (strcmp("Label", $display) === 0) {
			return $translate_option_label_button;
		} else {
			return $translate_option_flag_button . $translate_option_label_button;
		}

	}


	/**
	 * Method renders area for player.
	 *
	 * @param       string $audio_location Location where audio is being stored..
	 * @param       string $post_id Id of the post.
	 * @param       string $autoplay Autplay for player.
	 * @since  2.0.0
	 */
	private function include_audio_player( $language_code, $audio_location, $autoplay ) {

		if ( 'src' == $language_code ) {
			$new_audio_location = $audio_location;
		} else {
			$new_audio_location = str_replace( '.mp3', $language_code . '.mp3', $audio_location );
		}

		$common = $this->common;
		$controlsList = '';
		if ( !$common->is_audio_download_enabled() ) {
			$controlsList = ' controlsList="nodownload" ';
		}

		$response = '<div id="amazon-ai-player-container">
			<audio class="amazon-ai-player" id="amazon-ai-player" preload="none" controls ' . $autoplay . ' ' . $controlsList . '>
				<source type="audio/mpeg" src="' . $new_audio_location . '">
			</audio>
		</div>';

		return $response;
	}

	public function get_subscribe_part() {

		$part = '';

		$common = $this->common;
		$is_subscribe_button_enabled = $common->is_subscribe_button_enabled();
		if ($is_subscribe_button_enabled) {

			$button_image = apply_filters('amazon_ai_subscribe_button_image', 'https://d12ee1u74lotna.cloudfront.net/images/subscribe_general.png');
			$image = '<img src="' . $button_image . '" width="100" alt="Subscribe" >';
			$link = esc_url($common->get_subscribe_link());

			$part = '<a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $image . '</a>';
		}

		return $part;
	}



	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Amazonpolly_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amazonpolly_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/amazonpolly-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Amazonpolly_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amazonpolly_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/amazonpolly-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Adds customizer controls for the Podcast Feature.
	 *
	 * @since    1.0.0
	 * @param    object $wp_customize Instance of the WP Customize Manager.
	 */
	public function customize_register( $wp_customize ) {
		$wp_customize->add_section(
			'amazonpolly', array(
				'title'    => __( 'Amazon Polly', 'amazonpolly' ),
				'priority' => 30,
			)
		);

		$wp_customize->add_setting(
			'amazon_polly_podcast_icon', array(
				'default'    => 'image.jpg',
				'capability' => 'edit_theme_options',
				'type'       => 'option',
			)
		);

		$wp_customize->add_setting(
			'amazon_polly_podcast_email', array(
				'default'    => '',
				'capability' => 'edit_theme_options',
				'type'       => 'option',
			)
		);

		$wp_customize->add_setting(
			'amazon_polly_podcast_category', array(
				'default'    => 'News & Politics',
				'capability' => 'edit_theme_options',
				'type'       => 'option',
			)
		);

		$wp_customize->add_setting(
			'amazon_polly_podcast_explicit', array(
				'default'    => 'no',
				'capability' => 'edit_theme_options',
				'type'       => 'option',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Cropped_Image_Control(
				$wp_customize, 'podcast_icon', array(
					'settings'    => 'amazon_polly_podcast_icon',
					'section'     => 'amazonpolly',
					'label'       => __( 'iTunes image' ),
					'flex_width'  => false,
					'flex_height' => false,
					'width'       => 1400,
					'height'      => 1400,
				)
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize, 'podcast_email', array(
					'settings'    => 'amazon_polly_podcast_email',
					'section'     => 'amazonpolly',
					'label'       => __( 'iTunes contact email' ),
					'flex_width'  => false,
					'flex_height' => false,
					'width'       => 1400,
					'height'      => 1400,
				)
			)
		);

		$wp_customize->add_control(
			'amazon_polly_podcast_category', array(
				'type'     => 'select',
				'priority' => 10,
				'section'  => 'amazonpolly',
				'label'    => __( 'iTunes category' ),
				'choices'  => array(
					'Arts'                       => 'Arts',
					'Business'                   => 'Business',
					'Comedy'                     => 'Comedy',
					'Education'                  => 'Education',
					'Games & Hobbies'            => 'Games & Hobbies',
					'Government & Organizations' => 'Government & Organizations',
					'Health'                     => 'Health',
					'Kids'                       => 'Kids',
					'Music'                      => 'Music',
					'News & Politics'            => 'News & Politics',
					'Religion & Spirituality'		 => 'Religion & Spirituality',
					'Science & Medicine'         => 'Science & Medicine',
					'Society & Culture'          => 'Society & Culture',
					'Sports & Recreation'        => 'Sports & Recreation',
					'Technology'                 => 'Technology',
					'TV & Film'                  => 'TV & Film',
				),
			)
		);

		$wp_customize->add_control(
			'amazon_polly_podcast_explicit', array(
				'type'     => 'select',
				'priority' => 10,
				'section'  => 'amazonpolly',
				'label'    => __( 'iTunes explicit content' ),
				'choices'  => array(
					'yes' => 'Yes',
					'no'  => 'No',
				),
			)
		);
	}
}
