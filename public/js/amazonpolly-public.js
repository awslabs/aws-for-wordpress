/**
 * Additional JS if needed.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 */

(function( $ ) {
	'use strict';

	$( document ).ready(
		function(){

			var langs = ["src", "en", "es", "de", "pt", "fr"];

			function hideAll() {

				for (var i = 0; i < langs.length; i++) {
				    var lan = langs[i];

						//Stop player if running and hide it.
				    if ( $( '#amazon-polly-audio-play-'.concat(lan) ).length ) {
					    $( '#amazon-polly-audio-play-'.concat(lan) )[0].pause();
							$( '#amazon-polly-audio-play-'.concat(lan) ).hide();
				    }

						//Hide transcript
						if ( $( '#amazon-polly-transcript-'.concat(lan) ).length ) {
							$( '#amazon-polly-transcript-'.concat(lan) ).hide();
						}

						//Mark normal translation label
						if ( $( '#amazon-polly-trans-'.concat(lan) ).length ) {
							$( '#amazon-polly-trans-'.concat(lan) ).css("font-weight","normal");
						}
				}

			}

			function showPlayer(lan) {

				//Show player
				if ( $( '#amazon-polly-audio-play-'.concat(lan) ).length ) {
					$( '#amazon-polly-audio-play-'.concat(lan) ).show();
				}

				//Hide transcript
				if ( $( '#amazon-polly-transcript-'.concat(lan) ).length ) {
					$( '#amazon-polly-transcript-'.concat(lan) ).show();
				}

				//Mark bold translation label
				if ( $( '#amazon-polly-trans-'.concat(lan) ).length ) {
					$( '#amazon-polly-trans-'.concat(lan) ).css("font-weight","Bold");
				}

			}

			hideAll()
			showPlayer('src');

			$( '#amazon-polly-trans-src' ).click(
				function(){
					hideAll()
					showPlayer('src');
				}
			);

			$( '#amazon-polly-trans-en' ).click(
				function(){
					hideAll()
					showPlayer('en');
				}
			);

			$( '#amazon-polly-trans-es' ).click(
				function(){
					hideAll()
					showPlayer('es');
				}
			);

			$( '#amazon-polly-trans-de' ).click(
				function(){
					hideAll()
					showPlayer('de');
				}
			);

			$( '#amazon-polly-trans-fr' ).click(
				function(){
					hideAll()
					showPlayer('fr');
				}
			);

			$( '#amazon-polly-trans-pt' ).click(
				function(){
					hideAll()
					showPlayer('pt');
				}
			);

		}
	);

})( jQuery );
