/**
 * Additional JS if needed. All of the code for your admin-facing JavaScript source should reside in this file.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin/js
 */

(function( $ ) {
	'use strict';

	function amazonPollyProcessStep() {

		var amazonPollyProgressbar = $( "#amazon-polly-progressbar" );

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'polly_transcribe',
				nonce: pollyajax.nonce,
			},
			dataType: "json",
			beforeSend: function() {
				$('.amazon-polly-progress-label').show();
			},
			complete: function() {
			},
			success: function( response ) {
				if( 'done' == response.step ) {

				} else {
					amazonPollyProcessStep();
				}

				$( "#amazon-polly-progressbar" ).progressbar({
					value: response.percentage
				});

				amazonPollyProgressbar.progressbar( "value", response.percentage);
			}
		}).fail(function (response) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		});
	};

	$( document ).ready(
		function(){

			var amazonPollyProgressbar = $( "#amazon-polly-progressbar" ),
			amazonPollyProgressLabel = $( ".amazon-polly-progress-label" );

			$( '#amazon_polly_batch_transcribe' ).click(
				function(){
					$('#amazon_polly_batch_transcribe').hide();

					amazonPollyProgressbar.progressbar({
				      value: false,
				      change: function() {
				        amazonPollyProgressLabel.text( amazonPollyProgressbar.progressbar( "value" ) + "%" );
				      },
				      complete: function() {
				        amazonPollyProgressLabel.text( "Complete!" );
				      }
				    });
					amazonPollyProcessStep();
				}
			);

			$( '#amazon_polly_s3' ).change(
				function() {
					if ($( "#amazon_polly_s3" ).is( ':checked' )) {
						$( "#amazon_polly_s3_bucket_name_box" ).show();
						$( "#amazon_polly_cloudfront" ).prop( "disabled", false );
						$( "#amazon_polly_cloudfront_learnmore" ).prop( "disabled", false );
					} else {
						$( "#amazon_polly_s3_bucket_name_box" ).hide();
						$( "#amazon_polly_cloudfront" ).prop( "disabled", true );
						$( "#amazon_polly_cloudfront_learnmore" ).prop( "disabled", true );
					}
				}
			);

			$( '#amazon_polly_bulk_update_div' ).hide();

			$( '#amazon_polly_enable' ).change(
				function() {
					if ($( "#amazon_polly_enable" ).is( ':checked' )) {
						$( "#amazon_polly_post_options" ).show();
					} else {
						$( "#amazon_polly_post_options" ).hide();
					}
				}
			);

			$( '.wrap input, .wrap select' ).not('#amazon_polly_update_all').change(
				function() {
					$( '#amazon_polly_update_all' ).prop("disabled", true);
					$( '#amazon_polly_update_all' ).show();
					$( '#label_amazon_polly_update_all' ).show();
					$( '#amazon_polly_bulk_update_div' ).hide();
					$( '#amazon_polly_update_all_pricing_message' ).hide();
				}
			);

			$( '#amazon_polly_update_all' ).click(
				function(e) {
					e.stopPropagation();
					e.preventDefault();

					$( '#amazon_polly_update_all' ).hide();
					$( "#amazon_polly_bulk_update_div" ).show();
					$( '#amazon_polly_update_all_pricing_message' ).show();
				}
			);

			$( '#amazon_polly_price_checker_button' ).click(
				function(){
					var numer_of_characters = $( "#content_ifr" ).contents().find( "#tinymce" ).text().replace( / /g,'' ).length;
					var amazon_polly_price  = 0.000004;
					var total_price         = numer_of_characters * amazon_polly_price;

					alert( 'In total there are approximately ' + numer_of_characters + ' characters. Based on a rough estimation ($4 dollars per 1 million characters) it will cost you about $' + total_price + '  to convert this content into speech-based audio. Some or all of your costs might be covered by the Free Tier (conversion of 5 million characters per month for free, for the first 12 months, starting from the first request for speech). Learn more: https://aws.amazon.com/polly' );
				}
			);

			$( '#amazon_polly_s3_learnmore' ).click(
				function(){
					alert( 'With this option selected, audio files will not be saved on and streamed from local WordPress server, but instead, from Amazon S3 (Simple Storage Service). For additional information and pricing, please visit: https://aws.amazon.com/s3 ' );
				}
			);

			$( '#amazon_polly_cloudfront_learnmore' ).click(
				function(){
					alert( 'If you have created CloudFront distribution for your S3 bucket, you can provide here its name. For additional information and pricing, please visit following page: https://aws.amazon.com/cloudfront ' );
				}
			);

		}
	);

})( jQuery );
