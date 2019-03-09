<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed.
 *
 * @package WordPress
 */

header( 'Content-Type: ' . feed_content_type( 'rss2' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . '"?' . '>';

$amazon_pollycast = new Amazonpolly_PollyCast();

/**
 * Fires between the xml and rss tags in a feed.
 *
 * @since 4.0.0
 *
 * @param string $context Type of feed. Possible values include 'rss2', 'rss2-comments',
 *                        'rdf', 'atom', and 'atom-comments'.
 */
do_action( 'rss_tag_pre', 'rss2' );

// Podcast Icon
$podcast_icon = $amazon_pollycast->get_podcast_icon();

// iTunes
$itunes_email       = $amazon_pollycast->get_itunes_email();
$itunes_category    = $amazon_pollycast->get_itunes_category();
$itunes_explicit    = $amazon_pollycast->get_itunes_explicit();
$itunes_title       = $amazon_pollycast->get_itunes_title();
$itunes_description = $amazon_pollycast->get_itunes_description();

$common = new AmazonAI_Common();
$itunes_author = $common->get_podcast_author();

?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
	<?php
	/**
	 * Fires at the end of the RSS root to add namespaces.
	 *
	 * @since 2.0.0
	 */
	do_action( 'rss2_ns' );

	?>
>

<channel>
	<title><?php echo esc_attr($itunes_title); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php echo esc_attr($itunes_description); ?></description>
	<image>
		<url><?php echo esc_url( $podcast_icon ); ?></url>
		<title><?php echo esc_attr($itunes_title); ?></title>
		<link><?php bloginfo_rss( 'url' ); ?></link>
	</image>
	<itunes:owner>
		<itunes:name><?php echo $itunes_author; ?></itunes:name>
		<itunes:email><?php echo $amazon_pollycast->get_itunes_email(); ?></itunes:email>
	</itunes:owner>
	<itunes:category text="<?php echo esc_attr( $itunes_category ); ?>"></itunes:category>
	<itunes:explicit><?php echo esc_html( $itunes_explicit ); ?></itunes:explicit>
	<itunes:image href="<?php echo esc_url( $podcast_icon ); ?>"/>
	<itunes:author><?php echo $itunes_author; ?></itunes:author>
	<itunes:summary><?php	echo esc_attr($itunes_description); ?></itunes:summary>
	<itunes:subtitle><?php	echo esc_attr($itunes_description); ?></itunes:subtitle>
	<copyright><?php echo esc_html( $amazon_pollycast->get_copyright() ); ?></copyright>
	<lastBuildDate>
	<?php
		$date = get_lastpostmodified( 'GMT' );
		echo esc_html( $date ? mysql2date( 'r', $date, false ) : date( 'r' ) );
	?>
	</lastBuildDate>
	<!--<pubDate>Fri, 18 May 2012 00:00:00 EST</pubDate>-->
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<sy:updatePeriod>
	<?php
		$duration = 'hourly';

		/**
		 * Filters how often to update the RSS feed.
		 *
		 * @since 2.1.0
		 *
		 * @param string $duration The update period. Accepts 'hourly', 'daily', 'weekly', 'monthly',
		 *                         'yearly'. Default 'hourly'.
		 */
		echo esc_html( apply_filters( 'rss_update_period', $duration ) );
	?>
	</sy:updatePeriod>
	<sy:updateFrequency>
	<?php
		$frequency = '1';

		/**
		 * Filters the RSS update frequency.
		 *
		 * @since 2.1.0
		 *
		 * @param string $frequency An integer passed as a string representing the frequency
		 *                          of RSS updates within the update period. Default '1'.
		 */
		echo esc_html( apply_filters( 'rss_update_frequency', $frequency ) );
	?>
	</sy:updateFrequency>
	<?php
	while ( have_posts() ) :
		the_post();
		$audio_file      = $amazon_pollycast->get_audio_file_location( get_the_ID() );
		$categories_list = $amazon_pollycast->get_itunes_categories( get_the_ID() );
		?>
	<item>
		<title><?php the_title_rss(); ?></title>
		<link><?php echo esc_url( $audio_file ); ?></link>
		<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) ); ?></pubDate>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
		<enclosure url="<?php echo esc_url( $audio_file ); ?>" length="0" type="audio/mpeg"/>
		<guid><?php the_guid(); ?></guid>
		<itunes:author><![CDATA[<?php the_author(); ?>]]></itunes:author>
		<itunes:summary><![CDATA[<?php the_excerpt_rss(); ?>]]></itunes:summary>
		<itunes:keywords><![CDATA[<?php echo $categories_list; ?>]]></itunes:keywords>
		<itunes:explicit><?php echo $amazon_pollycast->get_itunes_explicit(); ?></itunes:explicit>
	</item>
	<?php endwhile; ?>
</channel>
</rss>
