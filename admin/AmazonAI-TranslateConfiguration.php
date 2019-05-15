<?php
/**
 * Class responsible for providing GUI for Amazon Translate configuration.
 *
 * @link       amazon.com
 * @since      2.5.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */
class AmazonAI_TranslateConfiguration
{
    private $common;
    private $aws_ok;
    private $s3_ok;


    public function amazon_ai_add_menu()
    {
        $this->plugin_screen_hook_suffix = add_submenu_page('amazon_ai', 'Translate', 'Translate', 'manage_options', 'amazon_ai_translate', array(
            $this,
            'amazonai_gui'
        ));
    }

    public function amazonai_gui()
    {
?>
			 <div class="wrap">
			 <div id="icon-options-translate" class="icon32"></div>
			 <h1>Translate Configuration</h1>
			 <form method="post" action="options.php">
					 <?php
        settings_errors();
        settings_fields("amazon_ai_translate");
        do_settings_sections("amazon_ai_translate");
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

        add_settings_section('amazon_ai_translate', "Amazon Translate configuration", array(
            $this,
            'translate_gui'
        ), 'amazon_ai_translate');
        add_settings_field('amazon_polly_trans_enabled', __('Enable translation support:', 'amazonpolly'), array(
            $this,
            'translation_enabled_gui'
        ), 'amazon_ai_translate', 'amazon_ai_translate', array(
            'label_for' => 'amazon_polly_trans_enabled'
        ));

        register_setting('amazon_ai_translate', 'amazon_polly_trans_enabled');

        if ($this->is_language_supported()) {
          if ( $this->common->is_translation_enabled() ) {
            if ( $this->common->validate_amazon_translate_access() ) {
              add_settings_field('amazon_ai_audio_for_translation_enabled', __('Enable audio for translations:', 'amazonpolly'), array(
                  $this,
                  'audio_for_translation_enabled_gui'
              ), 'amazon_ai_translate', 'amazon_ai_translate', array(
                  'label_for' => 'amazon_ai_audio_for_translation_enabled'
              ));
              add_settings_field('amazon_polly_trans_langs', __('Target languages:', 'amazonpolly'), array(
                  $this,
                  'translations_gui'
              ), 'amazon_ai_translate', 'amazon_ai_translate', array(
                  'label_for' => 'amazon_polly_trans_langs'
              ));

              register_setting('amazon_ai_translate', 'amazon_polly_trans_src_lang');
              register_setting('amazon_ai_translate', 'amazon_ai_audio_for_translation_enabled');


              foreach ($this->common->get_all_translable_languages() as $language_code) {
                register_setting('amazon_ai_translate', 'amazon_polly_trans_langs_' . $language_code, 'strval');
                register_setting('amazon_ai_translate', 'amazon_polly_trans_langs_' . $language_code . '_voice', 'strval');
                register_setting('amazon_ai_translate', 'amazon_polly_trans_langs_' . $language_code . '_label', 'strval');
                register_setting('amazon_ai_translate', 'amazon_polly_trans_langs_' . $language_code . '_display', 'strval');
              }
            }
          }
        }

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
    private function show_translate_option($src_lang, $voice_list, $lanuage, $language_name, $language_label, $selected_display_value)
    {

        if (empty($src_lang)) {
            $src_lang = 'en';
        }

        if ($src_lang == $lanuage) {
            //return;
        }

        $lan_option       = 'amazon_polly_trans_langs_' . $lanuage;
        $lan_voice_option = 'amazon_polly_trans_langs_' . $lanuage . '_voice';
        $lan_label_option = 'amazon_polly_trans_langs_' . $lanuage . '_label';
        $lan_display = 'amazon_polly_trans_langs_' . $lanuage . '_display';
        $disabled         = '';
        if ( $src_lang == $lanuage ) {
            $disabled = 'disabled';
        }

        #Some translations between languages are not supported by the service.
        #Details: https://docs.aws.amazon.com/translate/latest/dg/pairs.html
        if (!$this->common->is_translation_supported($src_lang, $lanuage)) {
          $disabled = 'disabled';
        }

        echo '<tr>';
        echo '<td><input type="checkbox" name="' . $lan_option . '" id="' . $lan_option . '" ' . $this->common->check_if_language_is_checked($lanuage, $src_lang) . ' ' . $disabled . '>' . $language_name . ' </td><td>';
        $voice_id = get_option($lan_voice_option);

        if ( $src_lang != $lanuage ) {
          if ($this->common->is_audio_for_translations_enabled()) {
            echo '&emsp;&emsp;Voice: <select name="' . $lan_voice_option . '" id="' . $lan_voice_option . '" ' . $disabled . '>';
            foreach ($voice_list['Voices'] as $voice) {
                if (strpos($voice['LanguageName'], $language_name) !== false) {
                    echo '<option value="' . esc_attr($voice['Id']) . '" ';
                    if (strcmp($voice_id, $voice['Id']) === 0) {
                        echo 'selected="selected"';
                    }

                    echo '>' . esc_attr($voice['LanguageName']) . ' - ' . esc_attr($voice['Id']) . '</option>';
                }
            }

            echo '</select>';
          }
        }
        echo '</td>';

        echo '<td>Label: <input type="text" width="70" class="regular-text" name="' . $lan_label_option . '" id="' . $lan_label_option . '" value="' . esc_attr( $language_label ) . '"></td>';


        echo '<td>';
        $display_values   = [ 'Flag', 'Label', 'Flag + Label' ];


        $only_labels = array("ar", "fa", "hi", "ms");
        if ( in_array($lanuage, $only_labels) ) {
            $display_values   = [ 'Label' ];
        }

        echo 'Display: <select name="' . $lan_display . '" id="' . $lan_display . '" >';
        foreach ( $display_values as $display_value ) {
          echo '<option value="' . esc_attr( $display_value ) . '" ';
          if ( strcmp( $selected_display_value, $display_value ) === 0 ) {
            echo 'selected="selected"';
          }
          echo '>' . esc_attr( $display_value ) . '</option>';
        }
        echo '</select>';
        echo '</td>';



        echo '</tr>';
    }

    /**
     * Render the translation target languages input.
     *
     * @since  2.0.0
     */
    public function translations_gui()
    {
        /**
         * Compare two voices for ordering purpose.
         *
         * @param           string $voice1                First voice.
         * @param           string $voice2                Second voice.
         * @since  1.0.0
         */
        function sort_voices($voice1, $voice2)
        {
            return strcmp($voice1['LanguageName'], $voice2['LanguageName']);
        }

        $translate_enabled = $this->common->is_translation_enabled();
            if ($translate_enabled) {
                $src_lang   = $this->common->get_source_language();
                $voice_list = $this->common->get_polly_voices();
                usort($voice_list['Voices'], 'sort_voices');
                echo '<table>';

                foreach ($this->common->get_all_translable_languages() as $language_code) {
                  $language_name = $this->common->get_language_name($language_code);
                  $language_label = $this->common->get_language_label($language_code);
                  $selected_display_value = $this->common->get_language_display($language_code);
                  $this->show_translate_option($src_lang, $voice_list, $language_code, $language_name, $language_label, $selected_display_value);
                }

                echo '</table>';
            } else {
                echo '<p class="description">Amazon Translate needs to be enabled</p>';
            }

    }

    private function is_language_supported() {

      $is_language_supported = false;
      $supported_languages = $this->common->get_all_translable_languages();
      $selected_source_language = $this->common->get_source_language();

      if (in_array($selected_source_language, $supported_languages)) {
        return true;
      } else {
        return false;
      }

    }


    /**
     * Render input for dciding if audio should be generated for translations.
     *
     * @since  2.5.0
     */
    public function audio_for_translation_enabled_gui() {

      $translate_enabled = $this->common->is_translation_enabled();
      $is_polly_enabled = $this->common->is_polly_enabled();

          if ($translate_enabled) {
            if ($is_polly_enabled) {
              echo '<input type="checkbox" name="amazon_ai_audio_for_translation_enabled" id="amazon_ai_audio_for_translation_enabled" ' . $this->common->checked_validator('amazon_ai_audio_for_translation_enabled') . ' > ';
            } else {
              echo '<p class="description">Amazon Polly (Text-To-Speech) needs to be enabled</p>';
            }
          } else {
              echo '<p class="description">Amazon Translate needs to be enabled</p>';
          }


    }


    /**
     * Render the enable Translation input.
     *
     * @since  2.0.0
     */
    public function translation_enabled_gui()
    {
        if ($this->is_language_supported()) {
        if ($this->common->validate_amazon_polly_access()) {
            if ($this->common->is_s3_enabled()) {
                $start_value = $this->common->checked_validator('amazon_polly_trans_enabled');
                $translate_accessible = $this->common->is_translation_enabled();
                $supported_regions    = array(
                    'us-east-1',
                    'us-east-2',
                    'us-west-2',
                    'eu-west-1'
                );
                $selected_region      = $this->common->get_aws_region();
                if (in_array($selected_region, $supported_regions)) {
                    echo '<input type="checkbox" name="amazon_polly_trans_enabled" id="amazon_polly_trans_enabled" ' . $this->common->checked_validator('amazon_polly_trans_enabled') . '> ';
                    if ('checked' == trim($start_value)) {
                        if (!$translate_accessible) {
                            echo '<p class="description"><b>Amazon Translate not accessible. You need to update your IAM policy.</b></p>';
                        }
                    }
                } else {
                    echo '<p class="description">You need to use one of following regions: N.Virginia, Ohio, Oregon, Ireland</p>';
                    update_option('amazon_polly_trans_enabled', '');
                }
            } else {
                echo '<p class="description">Amazon S3 Storage needs to be enabled</p>';
            }
        } else {
            echo '<p>Please verify your AWS Credentials are accurate</p>';
        }
      } else {
        echo '<p>Translate functionality not supported for this language</p>';
      }
    }

    function translate_gui()
    {

        // Empty

    }
}
