<?php
/**
 * Class responsible for providing GUI for Amazon PollyCast configuration.
 *
 * @link       amazon.com
 * @since      2.5.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_PodcastConfiguration {
	/**
	 * @var AmazonAI_Common
	 */
	private $common;

	/**
	 * AmazonAI_PodcastConfiguration constructor.
	 *
	 * @param AmazonAI_Common $common
	 */
	public function __construct(AmazonAI_Common $common) {
		$this->common = $common;
	}

	public function amazon_ai_add_menu() {
		$this->plugin_screen_hook_suffix = add_submenu_page( 'amazon_ai', 'Podcast', 'Podcast', 'manage_options', 'amazon_ai_podcast', array( $this, 'amazonai_gui' ));

	}

	public function amazonai_gui()
		{
	?>
				 <div class="wrap">
				 <div id="icon-options-podcast" class="icon32"></div>
				 <h1>Podcast - Amazon Pollycast</h1>
				 <form method="post" action="options.php">
						 <?php

				settings_errors();
				settings_fields("amazon_ai_podcast");
				do_settings_sections("amazon_ai_podcast");
				submit_button();

	?>
				 </form>

		 </div>
		 <?php
		}

		function display_options()
		{
			add_settings_section('amazon_ai_podcast', "Amazon Pollycast configuration", array($this,'podcast_gui'), 'amazon_ai_podcast');
			add_settings_field( 'amazon_polly_podcast_enabled', __( 'Pollycast enabled:', 'amazonpolly' ), array( $this, 'podcast_enabled_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_enabled' ) );
			register_setting('amazon_ai_podcast', 'amazon_polly_podcast_enabled');

			if ( $this->common->is_podcast_enabled() ) {
                add_settings_field( 'amazon_polly_podcast_title', __( 'Podcast title:', 'amazonpolly' ), array( $this, 'podcast_title_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_title' ) );
                add_settings_field( 'amazon_polly_podcast_description', __( 'Podcast description:', 'amazonpolly' ), array( $this, 'podcast_description_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_description' ) );
                add_settings_field( 'amazon_polly_podcast_author', __( 'iTunes author name:', 'amazonpolly' ), array( $this, 'podcast_author_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_author' ) );
                add_settings_field( 'amazon_polly_podcast_email', __( 'iTunes contact email:', 'amazonpolly' ), array( $this, 'podcast_email_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_email' ) );
                add_settings_field( 'amazon_polly_podcast_category', __( 'iTunes category:', 'amazonpolly' ), array( $this, 'podcast_category_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_category' ) );
                add_settings_field( 'amazon_polly_podcast_explicit', __( 'iTunes explicit content:', 'amazonpolly' ), array( $this, 'podcast_explicit_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_explicit' ) );
                add_settings_field( 'amazon_polly_podcast_icon', __( 'iTunes image:', 'amazonpolly' ), array( $this, 'podcast_icon_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_icon' ) );
                add_settings_field( 'amazon_polly_podcast_feedsize', __( 'Feed size:', 'amazonpolly' ), array( $this, 'podcast_feedsize_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_feedsize' ) );
                add_settings_field( 'amazon_polly_podcast_post_cat', __( 'Post categories:', 'amazonpolly' ), array( $this, 'podcast_post_cat_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_post_cat' ) );
                add_settings_field( 'amazon_polly_podcast_https', __( 'Use HTTPS for audio files:', 'amazonpolly' ), array( $this, 'podcast_https_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_https' ) );
                add_settings_field( 'amazon_polly_podcast_button', __( 'Show subscribe button:', 'amazonpolly' ), array( $this, 'podcast_subscribe_button_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_button' ) );
                add_settings_field( 'amazon_polly_podcast_button_link', __( 'Subscribe link:', 'amazonpolly' ), array( $this, 'podcast_subscribe_button_link_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'amazon_polly_podcast_button_link' ) );
                add_settings_field( 'amazon_polly_podcast_button', __( 'Include RSS2 Namespaces:', 'amazonpolly' ), array( $this, 'podcast_rss2namespace_gui' ), 'amazon_ai_podcast', 'amazon_ai_podcast', array( 'label_for' => 'podcast_rss2namespace' ) );


                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_title');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_description');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_author');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_email');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_category');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_explicit');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_icon');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_feedsize');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_post_cat');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_https');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_button');
                register_setting('amazon_ai_podcast', 'amazon_polly_podcast_button_link');
                register_setting('amazon_ai_podcast', 'podcast_rss2namespace');

            }
		}

		/**
		 * Render the Title input
		 *
		 * @since  3.0.2
		 */
		public function podcast_title_gui() {
            $value = get_option( 'amazon_polly_podcast_title' );
            echo sprintf('<input class="regular-text" name="amazon_polly_podcast_title" id="amazon_polly_podcast_title" value="%s"/>', esc_attr( $value ));
            echo sprintf('<p class="description">If not specified, the default title will be used: %s</p>', get_wp_title_rss());
		}

		/**
		 * Render the Description input
		 *
		 * @since  3.0.2
		 */
		public function podcast_description_gui() {
            $value = get_option( 'amazon_polly_podcast_description' );

            echo sprintf('<textarea rows="6" class="regular-text" name="amazon_polly_podcast_description" id="amazon_polly_podcast_description">%s</textarea>', esc_attr( $value ));
            echo sprintf(
                '<p class="description">If not specified, the default description will be used: %s</p>',
                get_bloginfo('description') ?: get_bloginfo('title')
            );
		}

		/**
		 * Render input for deciding if subscribe button should be displayed
		 *
		 * @since  3.0.2
		 */
		public function podcast_subscribe_button_gui() {

			echo '<input type="checkbox" name="amazon_polly_podcast_button" id="amazon_polly_podcast_button" ' . $this->common->checked_validator( 'amazon_polly_podcast_button' ) . '> ';

		}

        public function podcast_rss2namespace_gui() {
            echo '<input type="checkbox" name="podcast_rss2namespace" id="podcast_rss2namespace" ' . $this->common->checked_validator( 'podcast_rss2namespace' ) . '> ';
        }

		/**
		 * Render the HTTPS podcast input.
		 *
		 * @since  4.0.5
		 */
		public function podcast_https_gui() {

			echo '<input type="checkbox" name="amazon_polly_podcast_https" id="amazon_polly_podcast_https" ' . $this->common->checked_validator( 'amazon_polly_podcast_https' ) . '> ';

		}

		/**
		 * Render the possibility for specify author of the feed.
		 *
		 * @since  2.0.6
		 */
		public function podcast_subscribe_button_link_gui() {

			$value = $this->common->get_subscribe_link();
			echo '<input class="regular-text" name="amazon_polly_podcast_button_link" id="amazon_polly_podcast_button_link" value="' . esc_attr( $value ) . '"/>';
		}

		/**
		 * Render the possibility for specify author of the feed.
		 *
		 * @since  2.0.6
		 */
		public function podcast_author_gui() {

			$value = $this->common->get_podcast_author();

			echo '<input class="regular-text" name="amazon_polly_podcast_author" id="amazon_polly_podcast_author" value="' . esc_attr( $value ) . '"/>';
		}

		/**
		 * Render the possibility for specify category of posts which will be added in feed.
		 *
		 * @since  2.0.4
		 */
		public function podcast_post_cat_gui() {
			$value = get_option( 'amazon_polly_podcast_post_cat' );
			echo '<input class="regular-text" name="amazon_polly_podcast_post_cat" id="amazon_polly_podcast_post_cat" value="' . esc_attr( $value ) . '"/>';
		}


		/**
		 * Render UI for setting Amazon Pollycast feedsize
		 *
		 * @since  2.0.3
		 */
		public function podcast_feedsize_gui() {
			$value = $this->common->get_feed_size();
			echo '<input type="number" name="amazon_polly_podcast_feedsize" id="amazon_polly_podcast_feedsize" value="' . esc_attr( $value ) . '"/>';

		}

			/**
			 * Render the Update All input for this plugin
			 *
			 * @since  1.0.0
			 */
			public function podcast_icon_gui() {
				$query['autofocus[section]'] = 'amazonpolly';
				$section_link                = add_query_arg( $query, admin_url( 'customize.php' ) );
				echo '<p>Upload a podcast icon using the <a target="_blank" href="' . esc_url( $section_link ) . '">Customizer</a>.</p>';
			}


			/**
			 * Render the Update All input for this plugin
			 *
			 * @since  1.0.0
			 */
			public function podcast_explicit_gui() {

				$select_explicits = array(
					'yes',
					'no',
				);

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
			}

		/**
		 * Render the Update All input for this plugin
		 *
		 * @since  1.0.0
		 */
		public function podcast_category_gui() {
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
				'Religion & Spirituality',
				'Science & Medicine',
				'Society & Culture',
				'Sports & Recreation',
				'Technology',
				'TV & Film',
			);


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
		}

		/**
		 * Render the Update All input for this plugin
		 *
		 * @since  1.0.0
		 */
		public function podcast_email_gui() {
			$selected_email = get_option( 'amazon_polly_podcast_email' );
			echo '<input class="regular-text" name="amazon_polly_podcast_email" id="amazon_polly_podcast_email" value="' . esc_attr( $selected_email ) . '"/>';
		}

		/**
		 * Render the Podcast enabled input.
		 *
		 * @since  1.0.7
		 */
		public function podcast_enabled_gui() {

		if ($this->common->validate_amazon_polly_access()) {
				echo '<input type="checkbox" name="amazon_polly_podcast_enabled" id="amazon_polly_podcast_enabled" ' . $this->common->checked_validator( 'amazon_polly_podcast_enabled' ) . '> ';
				echo '<p class="description" for="amazon_polly_podcast_enabled">If enabled, Amazon Pollycast will be generated</p>';
			} else {
				echo '<p>Verify that your AWS credentials are accurate</p>';
			}

		}

		function podcast_gui() {
		if ( $this->common->is_podcast_enabled() ) {
			echo '<p>Amazon Pollycast available at: <a target = "_blank" href="' . esc_attr( get_feed_link( 'amazon-pollycast' ) ) . '">' . esc_html( get_feed_link( 'amazon-pollycast' ) ) . '</a></p>';
			echo '<p>Submit your Amazon Pollycast to iTunes iConnect: <a target = "_blank" href="https://podcastsconnect.apple.com/">' . esc_html( 'https://podcastsconnect.apple.com/' ) . '</a></p>';
		}
	}

}
