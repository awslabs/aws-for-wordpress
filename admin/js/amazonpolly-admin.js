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



	function amazonPollyTransProcessStep(phase, langs) {

		var amazonPollyTransProgressbar = $( "#amazon_polly_trans-progressbar" );
		var amazonPollyTransProgressLabel = $( ".amazon_polly_trans-label" );

		var post_id = $( "#post_ID" ).val();


		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'polly_translate',
				phase: phase,
				langs: langs,
				post_id: post_id,
				nonce: pollyajax.nonce,
			},
			dataType: "json",
			beforeSend: function() {
				$('.amazon_polly_trans-label').show();
			},
			complete: function() {
			},
			success: function( response ) {
				if( 'done' == response.step ) {

				} else {
					amazonPollyTransProcessStep('continue',response.langs);
				}

				$( "#amazon_polly_trans-progressbar" ).progressbar({
					value: response.percentage
				});

				amazonPollyTransProgressbar.progressbar( "value", response.percentage);

				amazonPollyTransProgressLabel.text( response.message );
			}
		}).fail(function (response) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		});
	};



	$( document ).ready(
		function(){

			var amazonPollyProgressbar = $( "#amazon-polly-progressbar" );
			var amazonPollyProgressLabel = $( ".amazon-polly-progress-label" );

			$( '#amazon_polly_batch_transcribe' ).click(
				function(){
					$('#amazon_polly_batch_transcribe').hide();

					amazonPollyProgressbar.progressbar({
				      value: false,
				      change: function() {
				        amazonPollyProgressLabel.text( "Starting" );
				      },
				      complete: function() {
				        amazonPollyProgressLabel.text( "Complete!" );
				      }
				    });
					amazonPollyProcessStep();
				}
			);


			var amazonPollyTraProgressbar = $( "#amazon_polly_trans-progressbar" );
			var amazonPollyTraProgressLabel = $( ".amazon_polly_trans-label" );

			$( '#amazon_polly_trans_button' ).click(
				function(){
					$('#amazon_polly_trans_button').hide();
					$('#amazon-polly-trans-info').hide();

					amazonPollyTraProgressbar.progressbar({
							value: false,
							change: function() {
								amazonPollyTraProgressLabel.text( amazonPollyTraProgressbar.progressbar( "value" ) + "%" );
							},
							complete: function() {
								amazonPollyTraProgressLabel.text( "Translation completed!" );
							}
						});
					amazonPollyTransProcessStep('start','');
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
			$( '#amazon_ai_plugin_cost_info' ).hide();

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

					if ( $('#amazon_ai_plugin_cost_info').is(":hidden") ) {
						$( '#amazon_ai_plugin_cost_info' ).show();
					} else {
						$( '#amazon_ai_plugin_cost_info' ).hide();
					}
				}
			);

			$( '#amazon_polly_s3_learnmore' ).click(
				function(){
					alert( 'With this option selected, audio files will not be saved to or streamed from the local WordPress server, but instead, from Amazon S3. For more information and pricing, see https://aws.amazon.com/s3 ' );
				}
			);

			$( '#amazon_polly_cloudfront_learnmore' ).click(
				function(){
					alert( 'If you have a CloudFront distribution for your S3 bucket, enter its name here. For more information and pricing, see https://aws.amazon.com/cloudfront ' );
				}
			);

			if( $('#amazon_polly_trans_button').length ) {
				if( $('#major-publishing-actions').length ) {
				     $( '#major-publishing-actions' ).append("<div id='amazon-polly-translate-reminder'>This content will be published in one language. To translate to other languages, choose <b>Translate</b> after publishing or updating.</div>");
				}
			}

		}
	);

})( jQuery );
