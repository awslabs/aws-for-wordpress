<?php
/**
 * Enables the Podcast RSS2 Feed
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 */

/**
 * The podcast functionality of the plugin.
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/pollycast
 * @author     WP Engine
 */
class Amazonpolly_PollyCast {

	/**
	 * The slug of this podcast feed.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $podcast_name    The slug of this podcast feed.
	 */
	private $podcast_name = 'amazon-pollycast';

	/**
	 * Adds the custom feed endpoint.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function create_podcast() {
		add_feed( $this->podcast_name, array( $this, 'render_rss' ) );
	}

	/**
	 * Renders the custom feed template.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function render_rss() {
		require_once dirname( __FILE__ ) . '/template-amazon-pollycast.php';
	}

	/**
	 * Updates the number of posts returned per podcast.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    object $query The WP default Query.
	 */
	public function filter_pre_get_posts( $query ) {

		// Only apply these filters for the Amazon PollyCast feed
		if ( $query->query_vars['feed'] !== $this->podcast_name ) {
			return;
		}

		// Only show items with audio files.
		$meta_query = array(
			array(
				'key'     => 'amazon_polly_audio_link_location',
				'compare' => 'EXISTS',
			),
		);
		$query->set( 'meta_query', $meta_query );

		$post_types_supported        = $this->get_posttypes_array();
		$query->set( 'post_type', $post_types_supported );

		// Filtering posts based on cateogiry (if specified)
		$post_category = get_option( 'amazon_polly_podcast_post_cat' );
		if ( !empty( $post_category )) {
			$query->set( 'category_name', $post_category );
		}

		$common = new AmazonAI_Common();
		$feed_size = $common->get_feed_size();

		// How many items to show in the Amazon PollyCast feed.
		$query->set( 'posts_per_rss', $feed_size );
		return $query;
	}

	/**
	 * Returns types of posts which should be in feed.
	 *
	 * @since    2.1.0
	 * @return   list list of types.
	 */
	public function get_posttypes_array() {
		$this->common = new AmazonAI_Common();
		$posttypes_array = $this->common->get_posttypes();
		$posttypes_array = explode( ' ', $posttypes_array );
		$posttypes_array = apply_filters( 'amazon_polly_post_types', $posttypes_array );

		return $posttypes_array;

	}


	/**
	 * Returns the location of the transcribed audio file.
	 *
	 * @since    1.0.0
	 * @param    int $post_id The ID of the post.
	 * @return   string The location of the audio file.
	 */
	public function get_audio_file_location( $post_id ) {
		$audio_file_location = get_post_meta( $post_id, 'amazon_polly_audio_link_location', true );

		$https_enabled = get_option( 'amazon_polly_podcast_https' );
		if ( empty( $https_enabled ) ) {
		  $audio_file_location = str_replace( 'https://', 'http://', $audio_file_location );
		}

		return $audio_file_location;
	}

	/**
	 * Returns the location of the Podcast Icon.
	 *
	 * @since    1.0.0
	 * @return   string The location of the Podcast Icon.
	 */
	public function get_podcast_icon() {
		$site_image            = '';
		$amazon_pollycast_icon = get_option( 'amazon_polly_podcast_icon' );
		if ( $amazon_pollycast_icon ) {
			$site_image = wp_get_attachment_url( get_option( 'amazon_polly_podcast_icon' ) );
		} else {
			$site_image = plugins_url() . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/img/amazon-polly-logo.jpg';
		}

		return $site_image;
	}

	/**
	 * Returns description
	 *
	 * @since    2.0.4
	 * @return   string The description
	 */
	public function get_itunes_description() {
        return get_option('amazon_polly_podcast_description') ?: get_bloginfo( 'description' ) ?: get_bloginfo( 'title' ) ?: "pollycast";
	}

	/**
	 * Returns description
	 *
	 * @since    2.0.4
	 * @return   string The description
	 */
	public function get_itunes_title() {
        return get_option('amazon_polly_podcast_title') ?: get_wp_title_rss();
	}

	/**
	 * Returns a the iTunes Email setting.
	 *
	 * @since    1.0.0
	 * @return   string The iTunes Email setting.
	 */
	public function get_itunes_email() {
		$itunes_email = get_option( 'amazon_polly_podcast_email' );
		$itunes_email = ( $itunes_email ) ? $itunes_email : '';
		return $itunes_email;
	}

	/**
	 * Returns a the iTunes Category setting.
	 *
	 * @since    1.0.0
	 * @return   string The iTunes Category setting.
	 */
	public function get_itunes_category() {
		$itunes_category = get_option( 'amazon_polly_podcast_category' );
		$itunes_category = ( $itunes_category ) ? $itunes_category : 'News &amp; Politics';
		return $itunes_category;
	}

	/**
	 * Returns a the iTunes Explicit setting.
	 *
	 * @since    1.0.0
	 * @return   string The iTunes Explicit setting.
	 */
	public function get_itunes_explicit() {
		$itunes_explicit = get_option( 'amazon_polly_podcast_explicit' );
		$itunes_explicit = ( $itunes_explicit ) ? $itunes_explicit : 'no';
		return $itunes_explicit;
	}

	/**
	 * Returns a comma delimited list of post categories for iTunes.
	 *
	 * @since    1.0.0
	 * @param    int $post_id The ID of the post.
	 * @return   string The comma delimited list of post categories for iTunes.
	 */
	public function get_itunes_categories( $post_id ) {
		$categories      = get_the_category( $post_id );
		$categories      = wp_list_pluck( $categories, 'name' );
		$categories_list = '';

		if ( ! empty( $categories ) ) {
			$categories_list = implode( ', ', $categories );
		}
		return $categories_list;
	}

	/**
	 * Returns a comma delimited list of post categories for iTunes.
	 *
	 * @since    1.0.0
	 * @param    int $post_id The ID of the post.
	 * @return   string The comma delimited list of post tags for iTunes.
	 */
	public function get_itunes_tags( $post_id ) {
		// Tags to string
		$tags      = get_the_tags( $post_id );
		$tags      = wp_list_pluck( $tags, 'name' );
		$tags_list = '';

		if ( ! empty( $tags ) ) {
			$tags_list = implode( ', ', $tags );
		}
		return $tags_list;
	}

	/**
	 * Returns a HTML decoded version of the provided input for use inside CDATA.
	 *
	 * @since    1.0.0
	 * @param    int $input The input to be decoded.
	 * @return   string The HTML decoded version of the provided input for use inside CDATA.
	 */
	public function filter_force_html_decode( $input ) {
		$input = wp_strip_all_tags( $input );
		return html_entity_decode( $input, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Returns a HTML decoded copyright date.
	 *
	 * @since    1.0.0
	 * @return   string The HTML decoded version of the copyright date.
	 */
	public function get_copyright() {
		$all_posts  = get_posts( 'post_status=publish&order=ASC' );
		$first_post = $all_posts[0];
		$first_date = $first_post->post_date_gmt;

		$copyright = 'Copyright &copy; ';
		if ( substr( $first_date, 0, 4 ) === date( 'Y' ) ) {
			$copyright .= date( 'Y' );
		} else {
			$copyright .= substr( $first_date, 0, 4 ) . '-' . date( 'Y' );
		}
		$copyright .= ' ' . get_bloginfo( 'name' );

		$copyright = $this->filter_force_html_decode( $copyright );
		return $copyright;
	}
}
