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
	 * The options name to be used in this plugin
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string      $option_name    Option name of this plugin
	 */
	private $option_name = 'amazon_polly';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * WordPress filter, responsible for adding audio play functionality for posts.
	 *
	 * @since    1.0.0
	 * @param      string $content       The content of the new post.
	 */
	public function amazon_polly_filter( $content ) {

		if ( get_post_meta( $GLOBALS['post']->ID, 'amazon_polly_enable', true ) === '1' ) {

			$post_id = $GLOBALS['post']->ID;

			$audio_location    = get_post_meta( $post_id, 'amazon_polly_audio_link_location', true );
			$selected_autoplay = get_option( 'amazon_polly_autoplay' );
			$player_label      = get_option( 'amazon_polly_player_label' );

			// Checks if this is single post view and if there is autoplay options selected.
			if ( is_singular() && ! empty( $selected_autoplay ) ) {
				$autoplay = 'autoplay';
			} else {
				$autoplay = '';
			}

			if ( is_singular() ) {
				$image  = __('<img src="https://d12ee1u74lotna.cloudfront.net/images/Voiced_by_Amazon_Polly_EN.png" width="100" >', $this->plugin_name);
				$voice_by_part = '<a href="https://aws.amazon.com/polly/" target="_blank" rel="noopener noreferrer">' . $image . '</a>';
			} else {
				$voice_by_part = '';
			}

			$original_content = $content;

			// Removing Amazon Polly special tags.
			$original_content = preg_replace( '/-AMAZONPOLLY-ONLYAUDIO-START-[\S\s]*?-AMAZONPOLLY-ONLYAUDIO-END-/', '', $original_content );
			$original_content = str_replace( '-AMAZONPOLLY-ONLYWORDS-START-', '', $original_content );
			$original_content = str_replace( '-AMAZONPOLLY-ONLYWORDS-END-', '', $original_content );

			$new_content = '';

			if ( is_singular() ) {

				$new_content = '
				<table id="amazon-polly-audio-table">
					<tr>

					<td id="amazon-polly-audio-tab">
						<div id="amazon-polly-label-tab">' . $player_label . '</div>
						' . $this->include_translations_options( $post_id ) . '
						' . $this->include_players( $post_id, $audio_location, $autoplay ) . '
						<div id="amazon-polly-by-tab">' . $voice_by_part . '</div>
					</td>
					</tr>
				</table>';
			}

			$selected_position = get_option( 'amazon_polly_position' );
			if ( strcmp( $selected_position, 'Do not show' ) === 0 ) {
				$content = $original_content;
			} elseif ( strcmp( $selected_position, 'After post' ) === 0 ) {
				$content = $original_content . $new_content;
			} else {
				$content = $new_content . $original_content;
			}
		}//end if

		return $content;
	}

	private function include_players( $post_id, $audio_location, $autoplay ) {

		$response = '';

		if ( ! $this->amazon_polly_is_translation_enabled() ) {
			$response = $this->add_player( $post_id, '', $audio_location, $autoplay, 'src' );
		} else {
			$src_lang = $this->get_src_lang();
			$response = $response . $this->add_player( $post_id, $src_lang, $audio_location, '', 'src' );
			$response = $response . $this->add_player( $post_id, $src_lang, $audio_location, '', 'en' );
			$response = $response . $this->add_player( $post_id, $src_lang, $audio_location, '', 'es' );
			$response = $response . $this->add_player( $post_id, $src_lang, $audio_location, '', 'de' );
			$response = $response . $this->add_player( $post_id, $src_lang, $audio_location, '', 'fr' );
			$response = $response . $this->add_player( $post_id, $src_lang, $audio_location, '', 'pt' );

		}

		return $response;
	}

	private function translations_available( $post_id ) {

		$options = 0;

		foreach ( $this->translate_langs as $supported_lan ) {

			$value = get_option( 'amazon_polly_trans_langs_' . $supported_lan );
			if ( ! empty( $value ) or ( 'en' == $supported_lan ) ) {
				$value = get_post_meta( $post_id, 'amazon_polly_translation_' . $supported_lan, true );
				if ( ! empty( $value ) ) {
					$options = $options + 1;
				}
			}
		}

		if ( $options > 0 ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Method return player and transcript HTML part.
	 *
	 * @param       string $audio_location Location where audio is being stored..
	 * @param       string $post_id Id of the post.
	 * @param       string $autoplay Autplay for player.
	 * @param       string $src_lang Source language of content.
	 * @since  2.0.0
	 */
	private function add_player( $post_id, $src_lang, $audio_location, $autoplay, $lan_code ) {

		$response = '';

		if ( $lan_code != $src_lang ) {
			$option = get_option( 'amazon_polly_trans_langs_' . $lan_code );
			if ( !empty( $option ) or ( 'src' == $lan_code ) or ( 'en' == $lan_code ) ) {
				$meta = get_post_meta( $post_id, 'amazon_polly_translation_' . $lan_code, true );
				if ( !empty( $meta ) or ( 'src' == $lan_code ) ) {
					// Display player .
					$response = $response . $this->include_play( $lan_code, $audio_location, $autoplay );

					// Display transcript area.
					$response = $response . $this->include_transcript( $post_id, $lan_code );

				}
			}
		}

		return $response;
	}

	/**
	 * Method renders area for player.
	 *
	 * @param       string $audio_location Location where audio is being stored..
	 * @param       string $post_id Id of the post.
	 * @param       string $autoplay Autplay for player.
	 * @since  2.0.0
	 */
	private function include_play( $lan_code, $audio_location, $autoplay ) {

		if ( 'src' == $lan_code ) {
			$new_audio_location = $audio_location;
		} else {
			$new_audio_location = str_replace( '.mp3', $lan_code . '.mp3', $audio_location );
		}

		$response = '<div id="amazon-polly-play-tab-' . $lan_code . '">
			<audio class="amazon-polly-audio-player" id="amazon-polly-audio-play-' . $lan_code . '" preload="none" controls ' . $autoplay . '>
				<source type="audio/mpeg" src="' . $new_audio_location . '">
			</audio>
		</div>';

		return $response;
	}

	/**
	 * Method renders area for showing transcript of post.
	 *
	 * @param       string $lan_code Language code.
	 * @param       string $post_id Id of the post.
	 * @since  2.0.0
	 */
	private function include_transcript( $post_id, $lan_code ) {

		$transcript_enabled = get_option( 'amazon_polly_transcript_enabled', '' );
		if ( 'on' == $transcript_enabled ) {
			if ( 'src' != $lan_code ) {
				$transcript = get_post_meta( $post_id, 'amazon_polly_transcript_' . $lan_code, true );
				return '<textarea class="amazon-polly-transcript-area" id="amazon-polly-transcript-' . $lan_code . '" readonly="readonly" >' . $transcript . '</textarea>';

			}
		}

		return '';

	}

	/**
	 * Method renders list of available translate languages..
	 *
	 * @param       string $post_id Id of the post.
	 * @since  2.0.0
	 */
	private function include_translations_options( $post_id ) {

		$response = '';

		if ( $this->amazon_polly_is_translation_enabled() ) {
			if ( $this->translations_available( $post_id ) ) {

				$src_lang = $this->get_src_lang();

				$src_label = get_option( 'amazon_polly_trans_langs_src_label', 'Source' );

				$trans_label = get_option( 'amazon_polly_trans_langs_label', 'Listen in other languages: ' );
				$response    = '<div class="amazon-polly-trans-label" id="amazon-polly-trans">' . $trans_label . ' </div>';
				$response    = $response . '<div class="amazon-polly-trans-label" id="amazon-polly-trans-src">' . $src_label . '</div>';

				$response = $response . $this->show_translate_label( $post_id, $src_lang, 'en', 'English' );
				$response = $response . $this->show_translate_label( $post_id, $src_lang, 'es', 'Español' );
				$response = $response . $this->show_translate_label( $post_id, $src_lang, 'de', 'Deutsch' );
				$response = $response . $this->show_translate_label( $post_id, $src_lang, 'fr', 'Francis' );
				$response = $response . $this->show_translate_label( $post_id, $src_lang, 'pt', 'Portugues' );

				$response = $response . '</div>';
			}
		}

		return $response;
	}

	private function show_translate_label( $post_id, $src_lang, $lan_code, $default_label ) {

		$response = '';

		if ( $lan_code != $src_lang ) {
			$option = get_option( 'amazon_polly_trans_langs_' . $lan_code );
			if ( !empty( $option ) or ( 'en' == $lan_code ) ) {
				$meta = get_post_meta( $post_id, 'amazon_polly_translation_' . $lan_code, true );
				if ( !empty( $meta ) ) {
					$es_label = get_option( 'amazon_polly_trans_langs_' . $lan_code . '_label', $default_label );
					$response = '<div class="amazon-polly-trans-label" id="amazon-polly-trans-' . $lan_code . '"> ' . $es_label . ' </div>';
				}
			}
		}

		return $response;

	}

	/**
	 * Check if Translation is enabled.
	 *
	 * @since  2.0.0
	 */
	private function amazon_polly_is_translation_enabled() {
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
	 * Checks if auto breaths are enabled.
	 *
	 * @since  1.0.7
	 */
	private function amazon_polly_is_s3_enabled() {
		$value = get_option( 'amazon_polly_s3', 'on' );

		if ( empty( $value ) ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}


		/**
		 * Returns source language.
		 *
		 * @since  2.0.0
		 */
	private function get_src_lang() {

		$value = get_option( 'amazon_polly_trans_src_lang', 'de' );
		return $value;
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
					'Religion'                   => 'Religion',
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
