<?php
/**
 * Class responsible for providing logic for translate functionality of the plugin.
 * Translate functionality is being provided using Amazon Translate service.
 *
 * @link       amazon.com
 * @since      2.0.3
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_Translator {

  /**
	 * Method validates if plugin has got access to Amazon Translate service by performing simple
	 * translation of single word. If it doesn't custom Error will be thrown.
	 *
   * @param           string $translate_client     Amazon Translate client reference.
	 * @since  2.0.0
	 */
	public function is_translate_accessible( $translate_client ) {

		$accessible = false;
		$option_value = get_option( 'amazon_polly_trans_validated' );

		// Method validates if plugin already detected that Amazon Translate
		// service can be reached. If not, it will try to reach it by calling
		// it and doing simple translate operation of word 'cloud'.
		if ( empty($option_value) ) {
			try {
				// Perform simple translation of single word.
				$translated_text_part = $translate_client->translateText(
				  array(
				    'SourceLanguageCode' => 'en',
				    'TargetLanguageCode' => 'es',
				    'Text'               => 'cloud',
				  )
				);

				// If we reached this place, it means that translate service is reachable
				update_option( 'amazon_polly_trans_validated', 'ok' );
				$accessible = true;
			} catch ( Exception $e ) {

				// If Amazon Translate service is not reachable Error will be thrown.
				update_option( 'amazon_polly_trans_validated', '' );
				$accessible = false;

			}
		} else {
			$accessible = true;
		}

		if ( !$accessible ) {
			update_option( 'amazon_polly_trans_enabled', '' );
			update_option( 'amazon_polly_trans_validated', '' );

			throw new TranslateAccessException('Cant perform Translate operation');
		}

		return $accessible;

	}

	public function translate_post( $common, $translate_client, $source_text, $source_language, $target_language) {

		$translated_text = '';
		$paragraphs = explode("\n", $source_text);
		foreach($paragraphs as $paragraph) {
			$is_image_paragraph = '';
			preg_match("/^\s*<img.*?src=.*?\>\s*$/", $paragraph, $is_image_paragraph);

			if (empty($is_image_paragraph)) {

				//$is_strong_paragraph = '';
				//preg_match("/^\s*<strong>.*?strong>\s*$/", $paragraph, $is_strong_paragraph);

				$clean_paragraph = $common->clean_paragraph($paragraph);
				$translated_paragraph = $this->translate( $translate_client, $clean_paragraph, $source_language, $target_language);

				//if (!empty($is_strong_paragraph)) {
				//	$translated_paragraph = "<strong>" . $translated_paragraph . "</strong>";
				///}

				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<strong>', '</strong>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<p><strong>', '</strong></p>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<h1>', '</h1>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<h2>', '</h2>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<h3>', '</h3>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<h4>', '</h4>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<h5>', '</h5>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<h6>', '</h6>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<p><em>', '</p></em>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<em>', '</em>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<p class="has-small-font-size">', '</p>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<p class="has-large-font-size">', '</p>');
				$translated_paragraph = $this->special_html_paragraph($common, $paragraph, $translated_paragraph, '<p class="has-huge-font-size">', '</p>');


			} else {
				$translated_paragraph = $paragraph;
			}

			$translated_text = $translated_text . "\n" . "<p>" . $translated_paragraph . "</p>";
		}

		return $translated_text;

	}


	private function special_html_paragraph($common, $paragraph, $translated_paragraph, $tag_beggining, $tag_ending) {

		if ( $common->startsWith($paragraph, $tag_beggining) and $common->endsWith($paragraph, $tag_ending) ) {
			$translated_paragraph = $tag_beggining . $translated_paragraph . $tag_ending;
		}

		return $translated_paragraph;

	}


  /**
   * Method translates sentences using Amazon Translate service.
   *
   * @param           string $translate_client     Amazon Translate client reference.
   * @param           string $source_text         Source text which should be translated.
   * @param           string $source_language     Source Language
   * @param           string $target_language     Target Language.
   * @since           1.0.0
   */
  public function translate( $translate_client, $source_text, $source_language, $target_language) {

		$logger = new AmazonAI_Logger();
		$logger->log(sprintf('%s Traslating', __METHOD__));

		// Translate functionality doesn't support SSML, ONLYAUDIO, ONLYWORDS tags
    $source_text = preg_replace( '/(\*\*AMAZONPOLLY\*SSML\*BREAK\*)(.*?)(\*\*\*)(.*?)(\*\*\*SSML\*\*)/', '', $source_text );
    $source_text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-START-', '', $source_text );
    $source_text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-END-', '', $source_text );
    $source_text = str_replace( '-AMAZONPOLLY-ONLYWORDS-START-', '', $source_text );
    $source_text = str_replace( '-AMAZONPOLLY-ONLYWORDS-END-', '', $source_text );

		// Because of limits of Amazon Translate service, we need to break original text info
		// smaller parts, which will be then send to the servicec and translation will be perfomed.
    $parts           = $this->break_for_translate( $source_text );
    $translated_text = '';
    $not_first       = false;
    foreach ( $parts as $part ) {

			// Between each call to Service we will sleep for 10 seconds. This allows
			// us to not be throttled.
      if ( $not_first ) {
        sleep( 10 );
      }

			// Performing actual translation.
      $translated_text_part = $translate_client->translateText(
        array(
          'SourceLanguageCode' => $source_language,
          'TargetLanguageCode' => $target_language,
          'Text'               => $part,
        )
      )['TranslatedText'];

			// We join all translated parts.
      $translated_text = $translated_text . ' ' . $translated_text_part;

    }

		$logger->log(sprintf('%s Translated text:', __METHOD__));
		$logger->log(sprintf('%s', $translated_text));

    return $translated_text;

  }

  /**
   * Method breaks text into multiple smaller parts.
   *
   * @param           string $text         Text which should be broken.
   * @since           2.0.0
   */
  private function break_for_translate( $text ) {

    $text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-START-', '', $text );
    $text = str_replace( '-AMAZONPOLLY-ONLYAUDIO-END-', '', $text );
    $text = preg_replace( '/-AMAZONPOLLY-ONLYWORDS-START-[\S\s]*?-AMAZONPOLLY-ONLYWORDS-END-/', '', $text );

    $parts   = [];
    $part_id = 0;

    $text_size = strlen( trim( $text ) );
    if ( $text_size > 0 ) {
      if ( $text_size <= 4500 ) {
        $parts[ $part_id ] = $text;
        $part_id++;
      } else {
        $words        = explode( ' ', $text );
        $current_part = '';
        $last_part    = '';

        foreach ( $words as $word ) {
          $word_length         = strlen( $word );
          $current_part_length = strlen( $current_part );
          if ( $word_length + $current_part_length < 4200 ) {
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

        $parts[ $part_id ] = $last_part;
        $part_id++;
      }
    }

    return $parts;

  }

	/**
   * Method will be called by user when clicking Translate button on GUI.
   *
   * @since           2.0.0
   */
	public function ajax_translate() {

		check_ajax_referer( 'pollyajaxnonce', 'nonce' );

		$polly = new AmazonAI_PollyService();
		$common = new AmazonAI_Common();
		$common->init();

		$logger = new AmazonAI_Logger();
		$logger->log(sprintf('%s Ajax Translate', __METHOD__));

		$post_id = $_POST['post_id'];
		$phase   = $_POST['phase'];
		$langs   = $_POST['langs'];

		$logger->log(sprintf('%s Phase ( %s )', __METHOD__, $phase));

		$step       = '';
		$percentage = 0;
		$message    = '';

		$all_langs = [];
		$index     = 0;

		$source_language  = $common->get_source_language();
		$translate_client = $common->get_translate_client();

		if ( empty( $source_language ) ) {
			$message    = 'Amazon Translate functionality needs to be enabled before publishing the post';
			$step       = 'done';
			$percentage = 100;

			$logger->log(sprintf('%s Transalte functionality is not enabled )', __METHOD__));

		} else {

			foreach ( $common->get_all_translable_languages() as $supported_lan ) {
				if ( $common->is_language_translable( $supported_lan ) and ( $supported_lan != $source_language ) ) {
					if ($common->if_translable_enabled_for_language($supported_lan)) {
						$all_langs[ $index ] = $supported_lan;
						$index++;
					}
				}
			}

			if ( 'start' == $phase ) {

				$langs = $all_langs;
				update_post_meta( $post_id, 'amazon_ai_source_language', $source_language );

			} else {

				$logger->log(sprintf('%s Languages ( %s )', __METHOD__, implode(" ", $langs)));

				# Check what language
				$language_code = array_shift( $langs );

				#Retrieve original text
				$content = get_post_field('post_content', $post_id);
				$clean_text = $common->clean_text( $post_id, false, false );

				$logger->log(sprintf('%s Translating from ( %s ) to ( %s )', __METHOD__, $source_language, $language_code));
				$wp_filesystem = $common->prepare_wp_filesystem();

				if ( $common->is_language_translable( $language_code ) and ( $language_code != $source_language ) ) {

					try {
						$clean_title = $common->clean_text( $post_id, false, true );
						$translated_title = $this->translate( $translate_client, $clean_title, $source_language,  $language_code);
						$translated_text = $this->translate_post( $common, $translate_client, $content, $source_language,  $language_code);
						update_post_meta( $post_id, 'amazon_polly_transcript_' . $language_code, $translated_text );
						update_post_meta( $post_id, 'amazon_polly_transcript_title_' . $language_code, $translated_title );
						$sentences = $common->break_text( $translated_text );

						// Create audio files for files only if this functionality is enabled.
						if ( $common->is_audio_for_translations_enabled() ) {
							$logger->log(sprintf('%s Starting preparing audio version', __METHOD__));
							$polly->convert_to_audio( $post_id, '', '', $sentences, $wp_filesystem, $language_code );
						}
					} catch(Exception $e) {
						error_log($e);
					}
				}

				$percentage = 100 - ( count( $langs ) / $index ) * 100;
			}//end if

			if ( empty( $langs ) ) {
				$step    = 'done';
				$message = 'Translation completed!';
			}
		}//end if

		$temp_langs = $langs;
		$next_lang = array_shift( $temp_langs );

		if ( ! empty( $next_lang ) ) {
			$logger->log(sprintf('%s Next language ( %s ))', __METHOD__, $next_lang));
			$message = 'Translating from ' . $common->get_language_name( $source_language ) . ' to ' . $common->get_language_name( $next_lang );
		} else {
			$message = 'Translation completed!';
		}

		if ( empty( $source_language ) ) {
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

}
