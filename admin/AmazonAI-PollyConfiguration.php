<?php
/**
 * Class responsible for providing GUI for Amazon Polly configuration.
 *
 * @link       amazon.com
 * @since      2.5.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_PollyConfiguration {
	/**
	 * @var AmazonAI_Common
	 */
	private $common;

	/**
	 * AmazonAI_PollyConfiguration constructor.
	 *
	 * @param AmazonAI_Common $common
	 */
	public function __construct(AmazonAI_Common $common) {
		$this->common = $common;
	}

	public function amazon_ai_add_menu() {
		$this->plugin_screen_hook_suffix = add_submenu_page( 'amazon_ai', 'Text-To-Speech', 'Text-To-Speech', 'manage_options', 'amazon_ai_polly', array( $this, 'amazonai_gui' ));

	}

	public function amazonai_gui()
	{
?>
			 <div class="wrap">
			 <div id="icon-options-polly" class="icon32"></div>
			 <h1>Text To Speech - Amazon Polly</h1>
			 <form method="post" action="options.php">
					 <?php

			settings_errors();
			settings_fields("amazon_ai_polly");
			do_settings_sections("amazon_ai_polly");
			submit_button();

?>
			 </form>

	 </div>
	 <?php
	}

	function display_options()
	{
        register_setting('amazon_ai_polly', 'amazon_ai_polly_enable');
        add_settings_section('amazon_ai_polly', "Amazon Polly configuration", array($this,'polly_gui'), 'amazon_ai_polly');
        add_settings_field( 'amazon_ai_source_language', __('Source language:', 'amazonpolly'), array($this,'source_language_gui'), 'amazon_ai_polly', 'amazon_ai_polly', array('label_for' => 'amazon_ai_source_language'));
        register_setting('amazon_ai_polly', 'amazon_ai_source_language');
        add_settings_field('amazon_ai_polly_enable', __('Enable text-to-speech support:', 'amazonpolly'), array($this,'polly_enabled_gui'), 'amazon_ai_polly', 'amazon_ai_polly', array('label_for' => 'amazon_ai_polly_enable'));
        register_setting('amazon_ai_polly', 'amazon_ai_polly_enable');

        if ($this->common->is_polly_enabled() ) {
            if ($this->common->validate_amazon_polly_access()) {
                if ($this->common->is_language_supported_for_polly()) {
                  add_settings_field( 'amazon_polly_voice_id', __( 'Voice name:', 'amazonpolly' ), array( $this, 'voices_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_voice_id' ) );
                  register_setting('amazon_ai_polly', 'amazon_polly_voice_id');

                    add_settings_field( 'amazon_polly_neural', __( 'Neural Text-To-Speech:', 'amazonpolly' ), array( $this, 'neural_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_neural' ) );
                    register_setting('amazon_ai_polly', 'amazon_polly_neural');
                    add_settings_field( 'amazon_polly_news', __( 'Newscaster Style:', 'amazonpolly' ), array( $this, 'news_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_news' ) );
                    register_setting('amazon_ai_polly', 'amazon_polly_news');
                    add_settings_field( 'amazon_polly_conversational', __( 'Conversational Style:', 'amazonpolly' ), array( $this, 'conversational_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_conversational' ) );
                    register_setting('amazon_ai_polly', 'amazon_polly_conversational');
                    add_settings_field( 'amazon_polly_sample_rate', __('Sample rate:', 'amazonpolly'), array($this,'sample_rate_gui'), 'amazon_ai_polly', 'amazon_ai_polly', array('label_for' => 'amazon_polly_sample_rate'));
        			add_settings_field( 'amazon_polly_auto_breaths', __( 'Automated breaths:', 'amazonpolly' ), array( $this, 'auto_breaths_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_auto_breaths_id' ) );
        			add_settings_field( 'amazon_polly_ssml', __( 'Enable SSML support:', 'amazonpolly' ), array( $this, 'ssml_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_ssml' ) );
        			add_settings_field( 'amazon_polly_lexicons', __( 'Lexicons:', 'amazonpolly' ), array( $this, 'lexicons_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_lexicons' ) );
        			add_settings_field( 'amazon_polly_speed', __( 'Audio speed [%]:', 'amazonpolly' ), array( $this, 'audio_speed_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_speed' ) );

        			add_settings_section( 'amazon_ai_playersettings', __( 'Player settings', 'amazonpolly' ), array( $this, 'playersettings_gui' ), 'amazon_ai_polly');
        			add_settings_field( 'amazon_polly_position', __( 'Player position:', 'amazonpolly' ), array( $this, 'playerposition_gui' ), 'amazon_ai_polly', 'amazon_ai_playersettings', array( 'label_for' => 'amazon_polly_position' ) );
        			add_settings_field( 'amazon_polly_player_label', __( 'Player label:', 'amazonpolly' ), array( $this, 'playerlabel_gui' ), 'amazon_ai_polly', 'amazon_ai_playersettings', array( 'label_for' => 'amazon_polly_player_label' ) );
        			add_settings_field( 'amazon_polly_defconf', __( 'New post default:', 'amazonpolly' ), array( $this, 'defconf_gui' ), 'amazon_ai_polly', 'amazon_ai_playersettings', array( '' => 'amazon_polly_defconf' ) );
        			add_settings_field( 'amazon_polly_autoplay', __( 'Autoplay:', 'amazonpolly' ), array( $this, 'autoplay_gui' ), 'amazon_ai_polly', 'amazon_ai_playersettings', array( 'label_for' => 'amazon_polly_autoplay' ) );


        			add_settings_section( 'amazon_ai_pollyadditional', __( 'Additional configuration', 'amazonpolly' ), array( $this, 'pollyadditional_gui' ), 'amazon_ai_polly');
        			add_settings_field( 'amazon_polly_update_all', __( 'Bulk update all posts:', 'amazonpolly' ), array( $this, 'update_all_gui' ),'amazon_ai_polly', 'amazon_ai_pollyadditional', array( 'label_for' => 'amazon_polly_update_all' ) );
        			add_settings_field( 'amazon_polly_add_post_title', __( 'Add post title to audio:', 'amazonpolly' ), array( $this, 'add_post_title_gui' ), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array( 'label_for' => 'amazon_polly_add_post_title' ) );
        			add_settings_field( 'amazon_polly_add_post_excerpt', __( 'Add post excerpt to audio:', 'amazonpolly' ), array( $this, 'add_post_excerpt_gui' ), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array( 'label_for' => 'amazon_polly_add_post_excerpt' ) );
                    add_settings_field( 'amazon_ai_medialibrary_enabled', __( 'Enable Media Library support:', 'amazonpolly' ), array( $this, 'medialibrary_enabled_gui' ), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array( 'label_for' => 'amazon_ai_medialibrary_enabled' ) );
                    add_settings_field( 'amazon_ai_skip_tags', __( 'Skip tags:', 'amazonpolly' ), array( $this, 'skiptags_gui' ), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array( 'label_for' => 'amazon_ai_skip_tags' ) );
                    add_settings_field( 'amazon_ai_download_enabled', __( 'Enable download audio:', 'amazonpolly' ), array( $this, 'download_gui' ), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array( 'label_for' => 'amazon_ai_download_enabled' ) );

                    add_settings_field('amazon_polly_s3', __('Store audio in Amazon S3:', 'amazonpolly'), array($this,'s3_gui'), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array('label_for' => 'amazon_polly_s3'));
                    add_settings_field('amazon_polly_posttypes', __('Post types:', 'amazonpolly'), array($this,'posttypes_gui'), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array('label_for' => 'amazon_polly_posttypes'));
                    add_settings_field('amazon_polly_cloudfront', __('Amazon CloudFront (CDN) domain name:', 'amazonpolly'), array($this,'cloudfront_gui'), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array('label_for' => 'amazon_polly_cloudfront'));
                    add_settings_field('amazon_polly_poweredby', __('Display "Powered by AWS":', 'amazonpolly'), array($this,'poweredby_gui'), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array('label_for' => 'amazon_polly_poweredby'));
                    add_settings_field('amazon_ai_logging', __('Enable logging:', 'amazonpolly'), array($this,'logging_gui'), 'amazon_ai_polly', 'amazon_ai_pollyadditional', array('label_for' => 'amazon_ai_logging'));

        			//Registration
                    register_setting('amazon_ai_polly', 'amazon_polly_s3');
                    register_setting('amazon_ai_polly', 'amazon_polly_posttypes');
                    register_setting('amazon_ai_polly', 'amazon_polly_cloudfront');
                    register_setting('amazon_ai_polly', 'amazon_polly_poweredby');
                    register_setting('amazon_ai_polly', 'amazon_ai_logging');

        			register_setting('amazon_ai_polly', 'amazon_polly_sample_rate');
        			register_setting('amazon_ai_polly', 'amazon_polly_auto_breaths');
        			register_setting('amazon_ai_polly', 'amazon_polly_ssml');
        			register_setting('amazon_ai_polly', 'amazon_polly_lexicons');
        			register_setting('amazon_ai_polly', 'amazon_polly_speed');

        			register_setting('amazon_ai_polly', 'amazon_polly_position');
        			register_setting('amazon_ai_polly', 'amazon_polly_player_label');
        			register_setting('amazon_ai_polly', 'amazon_polly_defconf');
        			register_setting('amazon_ai_polly', 'amazon_polly_autoplay');

        			register_setting('amazon_ai_polly', 'amazon_polly_update_all');
        			register_setting('amazon_ai_polly', 'amazon_polly_add_post_title');
        			register_setting('amazon_ai_polly', 'amazon_polly_add_post_excerpt');
                    register_setting('amazon_ai_polly', 'amazon_ai_medialibrary_enabled');
                    register_setting('amazon_ai_polly', 'amazon_ai_skip_tags');
                    register_setting('amazon_ai_polly', 'amazon_ai_download_enabled');
                }
            }
        }


	}

  /**
	 * Render the Enable Text-To-Speech functionality option.
	 *
	 * @since  2.5.0
	 */
  public function polly_enabled_gui() {
      if ($this->common->is_language_supported_for_polly()) {
          $value = $this->common->checked_validator( 'amazon_ai_polly_enable' );
          if ($this->common->validate_amazon_polly_access()) {
              echo '<input type="checkbox" name="amazon_ai_polly_enable" id="amazon_ai_polly_enable" ' . $this->common->checked_validator( 'amazon_ai_polly_enable' ) . '> ';
          } else {
              echo '<p>Verify that your AWS credentials are accurate</p>';
          }
      } else {
          echo '<p>Text-To-Speech functionality is not supported for this language</p>';
      }
  }

    /**
     * Render the 'Display "Powered by AWS" image' input.
     *
     * @since  2.6.0
     */
    function poweredby_gui()
    {
        $checked = $this->common->checked_validator("amazon_polly_poweredby");
        echo '<input type="checkbox" name="amazon_polly_poweredby" id="amazon_polly_poweredby" ' . esc_attr($checked) . ' > <p class="description"></p>';
        echo '<p class="description">Use this option to choose whether to display the <i>Display by AWS</i> logo on your website or add it to the content (like audio) that the plugin generates</p>';
    }

    /**
     * Render the translation source language input.
     *
     * @since  2.0.0
     */
    public function source_language_gui()
    {
        $selected_source_language = $this->common->get_source_language();
        echo '<select name="amazon_ai_source_language" id="amazon_ai_source_language" >';
        foreach ($this->common->get_all_languages() as $language_code) {
            $language_name = $this->common->get_language_name($language_code);
            echo '<option label="' . esc_attr($language_name) . '" value="' . esc_attr($language_code) . '" ';
            if (strcmp($selected_source_language, $language_code) === 0) {
                echo 'selected="selected"';
            }
            echo '>' . esc_attr__($language_name, 'amazon-polly') . '</option>';
        }
        echo '</select>';
    }

  private function is_language_supported() {

    $selected_source_language = $this->common->get_source_language();

    foreach ($this->common->get_all_polly_languages() as $language_code) {
      if (strcmp($selected_source_language, $language_code) === 0) {
        return true;
      }
    }

    return false;
  }

	/**
	 * Render the Update All input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function update_all_gui() {

			$message = $this->common->get_price_message_for_update_all();
			echo '<p>';
				echo '<button type="button" class="button" name="amazon_polly_update_all" id="amazon_polly_update_all" disabled>Bulk Update</button>';
				echo '<label id="label_amazon_polly_update_all" for="amazon_polly_update_all"> Changes must be saved before proceeding with a bulk update.</label>';
        echo '<p class="description" for="amazon_polly_update_all">Functionality is disabled in this plugin version.</p>';
			echo '</p>';
			echo '<div id="amazon_polly_bulk_update_div">';
				echo '<p id="amazon_polly_update_all_pricing_message" class="description">' . esc_html( $message ) . '</p>';
				echo '<p><button type="button" class="button button-primary" id="amazon_polly_batch_transcribe" >Bulk Update</button></p>';
				echo '<div id="amazon-polly-progressbar"><div class="amazon-polly-progress-label">Loading...</div></div>';
			echo '</div>';

	}

    /**
     * Render the 'use CloudFront' input.
     *
     * @since  1.0.0
     */
    public function cloudfront_gui()
    {
        $is_s3_enabled = $this->common->is_s3_enabled();
        if ( $is_s3_enabled ) {
            $cloudfront_domain_name = get_option('amazon_polly_cloudfront');
            echo '<input type="text" name="amazon_polly_cloudfront" class="regular-text" "id="amazon_polly_cloudfront" value="' . esc_attr($cloudfront_domain_name) . '" > ';
            echo '<p class="description">If you have a CloudFront distribution for your S3 bucket, enter the domain name. For more information and pricing, see <a target="_blank" href="https://aws.amazon.com/cloudfront">https://aws.amazon.com/cloudfront</a> </p>';
        } else {
            echo '<p class="description">Amazon S3 storage needs to be enabled</p>';
        }
    }

    /**
     * Render the 'store in S3' input.
     *
     * @since  1.0.0
     */
    function s3_gui()
    {
        $s3_bucket_name = $this->common->get_s3_bucket_name();
        $is_s3_enabled = $this->common->is_s3_enabled();
        if ( $is_s3_enabled ) {
            $checked                = ' checked ';
            $bucket_name_visibility = ' ';
        } else {
            $checked                = ' ';
            $bucket_name_visibility = 'display:none';
        }
        echo '<input type="checkbox" name="amazon_polly_s3" id="amazon_polly_s3" ' . esc_attr($checked) . ' > <p class="description"></p>';
        if ( $is_s3_enabled ) {
            echo '<label for="amazon_polly_s3" id="amazon_polly_s3_bucket_name_box" style="' . esc_attr($bucket_name_visibility) . '"> Your S3 bucket name is <b>' . esc_attr($s3_bucket_name) . '</b></label>';
        }
        echo '<p class="description">Audio files are saved to and streamed from Amazon S3. For more information, see <a target="_blank" href="https://aws.amazon.com/s3">https://aws.amazon.com/s3</a></p>';
    }

    /**
     * Render the 'Enable Logging' input.
     *
     * @since  2.6.2
     */
    function logging_gui()
    {
        $checked = $this->common->checked_validator("amazon_ai_logging");
        echo '<input type="checkbox" name="amazon_ai_logging" id="amazon_ai_logging" ' . esc_attr($checked) . ' > <p class="description"></p>';
    }

	/**
	 * Render the Add post excerpt to audio input.
	 *
	 * @since  2.0.0
	 */
	public function add_post_excerpt_gui() {

			echo '<input type="checkbox" name="amazon_polly_add_post_excerpt" id="amazon_polly_add_post_excerpt" ' . $this->common->checked_validator( 'amazon_polly_add_post_excerpt' ) . '> ';
			echo '<p class="description" for="amazon_polly_add_post_excerpt">If enabled, each audio file will have an excerpt of the post at the beginning.</p>';

	}


  public function download_gui() {

      echo '<input type="checkbox" name="amazon_ai_download_enabled" id="amazon_ai_download_enabled" ' . $this->common->checked_validator( 'amazon_ai_download_enabled' ) . '> ';
      echo '<p class="description" for="amazon_polly_add_post_excerpt">If enabled, viewers will see a download button next to the audio</p>';

  }


	/**
	 * Render the Add post title to audio input.
	 *
	 * @since  1.0.7
	 */
	public function add_post_title_gui() {

			echo '<input type="checkbox" name="amazon_polly_add_post_title" id="amazon_polly_add_post_title" ' . $this->common->checked_validator( 'amazon_polly_add_post_title' ) . '> ';
			echo '<p class="description" for="amazon_polly_add_post_title">If enabled, each audio file will start from the post\'s title.</p>';


	}

    /**
     * Render the Post Type input box.
     *
     * @since  1.0.7
     */
    public function posttypes_gui() {
        $posttypes = $this->common->get_posttypes();
        echo '<input type="text" class="regular-text" name="amazon_polly_posttypes" id="amazon_polly_posttypes" value="' . esc_attr( $posttypes ) . '"> ';
        echo '<p class="description" for="amazon_polly_posttypes">Post types in your WordPress environment</p>';
    }

    /**
     * Render the Neural GUI
     *
     */
    public function neural_gui() {

      $voice_id   = $this->common->get_voice_id();
      if ( $this->common->is_neural_supported_for_voice($voice_id) ) {
        if ($this->common->is_neural_supported_in_region()) {
            echo '<input type="checkbox" name="amazon_polly_neural" id="amazon_polly_neural" ' . $this->common->is_polly_neural_enabled() . '> ';
            echo '<p class="description" for="amazon_polly_neural">Delivers significant improvements in speech quality. Available only for US and UK English voices. Amazon Polly\'s Neural voices are priced at $16.00 per 1 million characters for speech or Speech Marks requested (when outside the free tier).</p>';
        } else {
            echo '<p class="description" for="amazon_polly_news">Option not supported in this region</p>';
        }
      } else {
        echo '<p class="description" for="amazon_polly_news">Option not supported for this voice</p>';
      }

    }

    /**
     * Render the Neural GUI
     *
     */
    public function news_gui() {

      if ($this->common->is_neural_supported_in_region()) {
        if ($this->common->is_polly_neural_enabled()) {
          $voice_id = $this->common->get_voice_id();
          if ( $this->common->is_news_style_for_voice($voice_id) ) {
            echo '<input type="checkbox" name="amazon_polly_news" id="amazon_polly_news" ' . $this->common->is_polly_news_enabled() . '> ';
          } else {
            echo '<p class="description" for="amazon_polly_news">Option not supported for this voice</p>';
          }
        } else {
          echo '<p class="description" for="amazon_polly_news">Neural needs to be enabled</p>';
        }
      } else {
        echo '<p class="description" for="amazon_polly_news">Option not supported in this region</p>';
      }
    }

    /**
     * Render the Conversational GUI
     *
     */
    public function conversational_gui() {

      if ($this->common->is_neural_supported_in_region()) {
        if ($this->common->is_polly_neural_enabled()) {
          $voice_id = $this->common->get_voice_id();
          if ( $this->common->is_conversational_style_for_voice($voice_id) ) {
            if ( !$this->common->is_polly_news_enabled()) {
              echo '<input type="checkbox" name="amazon_polly_conversational" id="amazon_polly_conversational" ' . $this->common->is_polly_conversational_enabled() . '> ';
            } else {
              echo '<p class="description" for="amazon_polly_conversational">Only one style can be used</p>';
            }
          } else {
            echo '<p class="description" for="amazon_polly_conversational">Option not supported for this voice</p>';
          }
        } else {
          echo '<p class="description" for="amazon_polly_conversational">Neural needs to be enabled</p>';
        }
      } else {
        echo '<p class="description" for="amazon_polly_conversational">Option not supported in this region</p>';
      }

    }

    /**
	 * Render the autoplay input.
	 *
	 * @since  1.0.0
	 */
	public function autoplay_gui() {

			$selected_autoplay = get_option( 'amazon_polly_autoplay' );

			if ( empty( $selected_autoplay ) ) {
				$checked = ' ';
			} else {
				$checked = ' checked ';
			}
			echo '<input type="checkbox" name="amazon_polly_autoplay" id="amazon_polly_autoplay" ' . esc_attr( $checked ) . '> ';
			echo '<p class="description" for="amazon_polly_autoplay">Automatically play audio content when page loads</p>';

	}

	/**
	 * Render the Default Configuration input.
	 *
	 * @since  1.0.0
	 */
	public function defconf_gui() {

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


	}

    /**
  	 * Render the Player Label input.
  	 *
  	 * @since  1.0.3
  	 */
  public function skiptags_gui() {

  		$tags = get_option( 'amazon_ai_skip_tags' );
  		echo '<input type="text" class="regular-text" name="amazon_ai_skip_tags" id="amazon_ai_skip_tags" value="' . esc_attr( $tags ) . '"> ';

  }

	/**
	 * Render the Player Label input.
	 *
	 * @since  1.0.3
	 */
public function playerlabel_gui() {

		$player_label = get_option( 'amazon_polly_player_label' );
		echo '<input type="text" class="regular-text" name="amazon_polly_player_label" id="amazon_polly_player_label" value="' . esc_attr( $player_label ) . '"> ';


}

	/**
	 * Render the Position input.
	 *
	 * @since  1.0.0
	 */
	public function playerposition_gui() {

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


	}

	/**
	 * Render the Sample Rate input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function sample_rate_gui() {

  			$sample_rate  = $this->common->get_sample_rate();
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


	}

	/**
	 * Render the Player Label input.
	 *
	 * @since  1.0.12
	 */
	public function lexicons_gui() {

			$lexicons = $this->common->get_lexicons();
			echo '<input type="text" class="regular-text" name="amazon_polly_lexicons" id="amazon_polly_lexicons" value="' . esc_attr( $lexicons ) . '"> ';
			echo '<p class="description" for="amazon_polly_lexicons">Specify the lexicons names, seperated by spaces, that you have uploaded to your AWS account</p>';

	}

	/**
	 * Render the autoplay input.
	 *
	 * @since  1.0.5
	 */
	public function audio_speed_gui() {

			$speed = $this->common->get_audio_speed();
			echo '<input type="number" name="amazon_polly_speed" id="amazon_polly_speed" value="' . esc_attr( $speed ) . '">';

	}


  /**
   * Render the enable SSML input.
   *
   * @since  1.0.7
   */
  public function medialibrary_enabled_gui() {

      $is_s3_enabled = $this->common->is_s3_enabled();
      if ( !$is_s3_enabled ) {
        $is_medialibrary_enabled = $this->common->is_medialibrary_enabled();

        if ( $is_medialibrary_enabled ) {
          $checked = ' checked ';
        } else {
          $checked = ' ';
        }

        echo '<input type="checkbox" name="amazon_ai_medialibrary_enabled" id="amazon_ai_medialibrary_enabled" ' . esc_attr( $checked ) . '> ';
      } else {
        echo '<p class="description">Local storage needs to be enabled</p>';
      }

  }

	/**
	 * Render the enable SSML input.
	 *
	 * @since  1.0.7
	 */
	public function ssml_gui() {

			$is_s3_enabled = $this->common->is_s3_enabled();
			if ( $is_s3_enabled ) {
				$is_ssml_enabled = $this->common->is_ssml_enabled();

				if ( $is_ssml_enabled ) {
					$checked = ' checked ';
				} else {
					$checked = ' ';
				}

				echo '<input type="checkbox" name="amazon_polly_ssml" id="amazon_polly_ssml" ' . esc_attr( $checked ) . '> ';
			} else {
				echo '<p class="description">Amazon S3 storage needs to be enabled</p>';
			}

	}

	/**
	 * Render the Automated Breath input.
	 *
	 * @since  1.0.7
	 */
	public function auto_breaths_gui() {
	    echo '<input type="checkbox" name="amazon_polly_auto_breaths" id="amazon_polly_auto_breaths" ' . $this->common->checked_validator( 'amazon_polly_auto_breaths' ) . '> ';
	    echo '<p class="description" for="amazon_polly_auto_breaths">Creates breathing noises at appropriate intervals</p>';
	}

	/**
	 * Render the Polly Voice input for this plugin
	 *
	 * @since  1.0.0
	 */
	public function voices_gui() {

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


			$voice_id   = $this->common->get_voice_id();
			$voice_list = $this->common->get_polly_voices();
      $language_name = $this->common->get_source_language_name();

			echo '<select name="amazon_polly_voice_id" id="amazon_polly_voice_id">';
			usort( $voice_list['Voices'], 'sort_voices' );
			foreach ( $voice_list['Voices'] as $voice ) {
        if (strpos($voice['LanguageName'], $language_name) !== false) {
  				echo '<option value="' . esc_attr( $voice['Id'] ) . '" ';
  				if ( strcmp( $voice_id, $voice['Id'] ) === 0 ) {
  					echo 'selected="selected"';
  				}
  				echo '>' . esc_attr( $voice['LanguageName'] ) . ' - ' . esc_attr( $voice['Id'] ) . '</option>';
        }
			}
			echo '</select>';


	}

	/**
	 * Render the Access Key input for this plugin
	 *
	 * @since  1.0.0
	 */
	function access_key_gui()
	{
			$access_key = get_option('amazon_polly_access_key');
			echo '<input type="text" class="regular-text" name="amazon_polly_access_key" id="amazon_polly_access_key" value="' . esc_attr($access_key) . '" autocomplete="off"> ';
			echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';

	}

	function playersettings_gui() {
		// Empty
	}

	function polly_gui()
	{
			//Empty
	}

	function pollyadditional_gui() {
		//Empty
	}

}
