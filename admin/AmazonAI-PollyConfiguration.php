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

  private $common;

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

		$this->common = new AmazonAI_Common();
    $this->common->init();




			add_settings_section('amazon_ai_polly', "Amazon Polly configuration", array($this,'polly_gui'), 'amazon_ai_polly');

        add_settings_field('amazon_ai_polly_enable', __('Enable text-to-speech support:', 'amazonpolly'), array($this,'polly_enabled_gui'), 'amazon_ai_polly', 'amazon_ai_polly', array('label_for' => 'amazon_ai_polly_enable'));
        register_setting('amazon_ai_polly', 'amazon_ai_polly_enable');

        if ($this->is_language_supported()) {
          if ($this->common->validate_amazon_polly_access() ) {
            if ($this->common->is_polly_enabled()) {
              add_settings_field( 'amazon_polly_sample_rate', __('Sample rate:', 'amazonpolly'), array($this,'sample_rate_gui'), 'amazon_ai_polly', 'amazon_ai_polly', array('label_for' => 'amazon_polly_sample_rate'));
        			add_settings_field( 'amazon_polly_voice_id', __( 'Voice name:', 'amazonpolly' ), array( $this, 'voices_gui' ), 'amazon_ai_polly', 'amazon_ai_polly', array( 'label_for' => 'amazon_polly_voice_id' ) );
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

        			//Registration
        			register_setting('amazon_ai_polly', 'amazon_polly_sample_rate');
        			register_setting('amazon_ai_polly', 'amazon_polly_voice_id');
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

    if ($this->is_language_supported()) {
      $value = $this->common->checked_validator( 'amazon_ai_polly_enable' );
      if ($this->common->validate_amazon_polly_access()) {
        echo '<input type="checkbox" name="amazon_ai_polly_enable" id="amazon_ai_polly_enable" ' . $this->common->checked_validator( 'amazon_ai_polly_enable' ) . '> ';
      } else {
        echo '<p>Please verify your AWS Credentials are accurate</p>';
      }
    } else {
      echo '<p>Text-To-Speech functionality not supported for this language</p>';
    }

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
				echo '<button type="button" class="button" name="amazon_polly_update_all" id="amazon_polly_update_all">Bulk Update</button>';
				echo '<label id="label_amazon_polly_update_all" for="amazon_polly_update_all"> Changes must be saved before proceeding with a bulk update.</label>';
			echo '</p>';
			echo '<div id="amazon_polly_bulk_update_div">';
				echo '<p id="amazon_polly_update_all_pricing_message" class="description">' . esc_html( $message ) . '</p>';
				echo '<p><button type="button" class="button button-primary" id="amazon_polly_batch_transcribe" >Bulk Update</button></p>';
				echo '<div id="amazon-polly-progressbar"><div class="amazon-polly-progress-label">Loading...</div></div>';
			echo '</div>';

	}


	/**
	 * Render the Add post excerpt to audio input.
	 *
	 * @since  2.0.0
	 */
	public function add_post_excerpt_gui() {

			echo '<input type="checkbox" name="amazon_polly_add_post_excerpt" id="amazon_polly_add_post_excerpt" ' . $this->common->checked_validator( 'amazon_polly_add_post_excerpt' ) . '> ';
			echo '<p class="description" for="amazon_polly_add_post_excerpt">If enabled, each audio file will have post excerpt at the beginning.</p>';

	}


  public function download_gui() {

      echo '<input type="checkbox" name="amazon_ai_download_enabled" id="amazon_ai_download_enabled" ' . $this->common->checked_validator( 'amazon_ai_download_enabled' ) . '> ';
      echo '<p class="description" for="amazon_polly_add_post_excerpt">If enabled, browser will show download button next to audio</p>';

  }


	/**
	 * Render the Add post title to audio input.
	 *
	 * @since  1.0.7
	 */
	public function add_post_title_gui() {

			echo '<input type="checkbox" name="amazon_polly_add_post_title" id="amazon_polly_add_post_title" ' . $this->common->checked_validator( 'amazon_polly_add_post_title' ) . '> ';
			echo '<p class="description" for="amazon_polly_add_post_title">If enabled, each audio file will start from post title.</p>';


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
			echo '<p class="description" for="amazon_polly_lexicons">Specify lexicons names (seperated by space), which you have uploaded to your AWS account</p>';

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
        echo '<p class="description">Local Storage needs to be enabled</p>';
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
				echo '<p class="description">Amazon S3 Storage needs to be enabled</p>';
			}

	}

	/**
	 * Render the Automated Breath input.
	 *
	 * @since  1.0.7
	 */
	public function auto_breaths_gui() {

			echo '<input type="checkbox" name="amazon_polly_auto_breaths" id="amazon_polly_auto_breaths" ' . $this->common->checked_validator( 'amazon_polly_auto_breaths' ) . '> ';
			echo '<p class="description" for="amazon_polly_auto_breaths">If enabled, Amazon Polly automatically creates breathing noises at appropriate intervals</p>';


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
