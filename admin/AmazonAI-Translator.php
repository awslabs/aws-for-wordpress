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
	 * translation of single word.
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
		}

		return $accessible;

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


}
