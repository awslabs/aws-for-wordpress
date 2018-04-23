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

			$audio_location    = get_post_meta( $GLOBALS['post']->ID, 'amazon_polly_audio_link_location', true );
			$selected_autoplay = get_option( 'amazon_polly_autoplay' );
			$player_label      = get_option( 'amazon_polly_player_label' );

			// Checks if this is single post view and if there is autoplay options selected.
			if ( is_singular() && ! empty( $selected_autoplay ) ) {
				$autoplay = 'autoplay';
			} else {
				$autoplay = '';
			}

			if ( is_singular() ) {
				$image         = 'https://d12ee1u74lotna.cloudfront.net/images/Voiced_by_Amazon_Polly_EN.png';
				$voice_by_part = '<a href="https://aws.amazon.com/polly/" target="_blank" rel="noopener noreferrer"><img src=" ' . $image . '" width="100" ></a>';
			} else {
				$voice_by_part = '';
			}

			$original_content = $content;

			// Removing Amazon Polly special tags.
			$original_content = preg_replace( '/-AMAZONPOLLY-ONLYAUDIO-START-[\S\s]*?-AMAZONPOLLY-ONLYAUDIO-END-/', '', $original_content );
			$original_content = str_replace( '-AMAZONPOLLY-ONLYWORDS-START-', '', $original_content );
			$original_content = str_replace( '-AMAZONPOLLY-ONLYWORDS-END-', '', $original_content );

			$new_content = '

			<table id="amazon-polly-audio-table">
				<tr>

				<td id="amazon-polly-audio-tab">
					<div id="amazon-polly-label-tab">' . $player_label . '</div>
					<div id="amazon-polly-play-tab">
						<audio id="amazon-polly-audio-play" preload="none" controls ' . $autoplay . '>
							<source type="audio/mpeg" src="' . $audio_location . '">
						</audio>
					</div>
					<div id="amazon-polly-by-tab">' . $voice_by_part . '</div>
				</td>
				</tr>
			</table>';

			$selected_position = get_option( 'amazon_polly_position' );
			if ( strcmp( $selected_position, 'Do not show' ) === 0 ) {
				$content = $original_content;
			} elseif ( strcmp( $selected_position, 'After post' ) === 0 ) {
				$content = $original_content . $new_content;
			} else {
				$content = $new_content . $original_content;
			}
		}//end if

		// Returns the content.
		return $content;
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
