=== AWS for WordPress ===
Contributors: awslabs, tstachlewski, wpengine, stevenkword
Tags: AWS, Amazon Web Services, WP Engine, Cloud, Text-to-Speech, Amazon Polly, Amazon Translate, Translate, Translation, Podcast, AI, Amazon CloudFront
Requires at least: 3.0.1
Requires PHP: 5.6
Tested up to: 5.3
Stable tag: 4.2.2
License: GPLv3 ONLY
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Create audio versions of your posts, translate them into other languages, and create podcasts. Integrate with Amazon Alexa to listen to your posts on Alexa-enabled devices. Use Amazon CloudFront to accelerate your website and provide a faster, more reliable viewing experience.

== Installation ==

For installation instructions, see the documentation at https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugIn.html.

== Configuration ==

For configuration instructions, see the documentation at https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugIn.html.

== Frequently Asked Questions ==

= Do I need to have an AWS account to use the plugin? =

Yes. You can create one here: https://aws.amazon.com/free/

= What is Amazon Polly? =

Amazon Polly is a text-to-speech (TTS) service that uses advanced deep learning technologies to turn text into lifelike speech that sounds like a human voice.

= How much does Amazon Polly cost? =

For current pricing information, see the Amazon Polly pricing page at https://aws.amazon.com/polly/pricing/.

= Does Amazon Polly participate in the AWS Free Tier? =

Yes. As part of the AWS Free Usage Tier (https://aws.amazon.com/free/), you can get started with Amazon Polly for free. Upon sign-up, new Amazon Polly customers can synthesize up to 5 million characters for free each month for the first 12 months.

= Which languages can I use with Amazon Polly =

For the full list of languages that Amazon Polly supports, see https://docs.aws.amazon.com/polly/latest/dg/SupportedLanguage.html.

= Does the plugin delete my Amazon Polly audio files if I delete the plugin? =

No. All audio files are preserved. Depending on your configuration, they will be stored on your WordPress server, or in your Amazon S3 bucket.

= Does the plugin support SSML tags with Amazon Polly? =

Amazon Polly supports multiple SSML tags. For more information, see https://docs.aws.amazon.com/polly/latest/dg/ssml.html.

The AWS for WordPress plugin currently supports only the <break> SSML tag. For more information, see https://docs.aws.amazon.com/polly/latest/dg/supportedtags.html.

To use SSML tags, you need to enable Amazon S3 as the storage location for your files, and enable SSML support on the plugin configuration page. Then, in the wizard for creating a new WordPress post, you can add SSML tags. Here is an example of content with the SSML break tag:

Mary had a little lamb <ssml><break time="3s"/></ssml> whose fleece was white as snow.

= Is there additional cost for storing Amazon Polly audio files on S3? =

Yes. For current pricing information, see the Amazon S3 pricing page at https://aws.amazon.com/s3/pricing/.

= How do I view my Amazon Pollycast feed? =

Append '/amazon-pollycast/' to any page URL. For example:

example.com/feed/amazon-pollycast/
example.com/category/news/feed/amazon-pollycast/
example.com/author/john/feed/amazon-pollycast/

= How do I publish my Pollycast podcast with iTunes? =

Submit your Amazon Pollycast to the iTunes iConnect directory at https://podcastsconnect.apple.com/.

= How is the Amazon Polly bulk update cost calculated? =

If you bulk update fewer than 100 posts, the plugin calculates the number of characters in all of the posts and then, based on Amazon Polly pricing, provides an estimate for the cost of conversion. If you bulk update more than 100 posts, the plugin calculates the average number of characters in the first 100 posts and then, based on this, estimates the total number of characters in all posts to provide a cost estimate.

= What kind of filters can I use with Amazon Polly? =

The plugin has the following filters:

amazon_polly_post_types – Specifies which WordPress post types should be used by the plugin. The default value is 'post'.

amazon_polly_content - Enables you to modify the content of the post before it is sent to the Amazon Polly service for text-to-speech conversion.

amazon_polly_s3_bucket_name - Enables you to define your own bucket name where audio files will be stored. The bucket must already exist and be in the same region as you specify in the plugin configuration. You must also modify the IAM policy to provide access to this bucket.

= What are lexicons? =

Pronunciation lexicons enable you to customize the pronunciation of words. In the plugin configuration, you can provide the names of the lexicons that you have previously uploaded to your AWS account in the region specified in the plugin configuration. You can provide up to five lexicons, separated by spaces.

= What is Amazon Translate? =

Amazon Translate is a neural machine translation service that delivers fast, high-quality, and affordable language translation.

= How much does Amazon Translate cost? =

For current pricing information, see the Amazon Translate pricing page at https://aws.amazon.com/translate/pricing/.

= Does Amazon Translate participate in the AWS Free Tier? =

Yes. As part of the AWS Free Usage Tier (https://aws.amazon.com/free/), you can get started with Amazon Translate for free. Upon sign-up, new Amazon Translate customers can translate up to 2 million characters for free each month for the first 12 months.

= Which languages can I use with Amazon Translate? =

For the full list of languages that Amazon Translate supports, see https://docs.aws.amazon.com/translate/latest/dg/what-is.html.

= If I enable Amazon Translate functionality, will my post be translated automatically? =

No. To translate the post, you must first publish the post, and then enable the Amazon Translate functionality for the specific post by choosing the Translate button.

= Can I use Amazon Translate if I want to store files on the WordPress server? =

No. You must use Amazon S3 as the storage location for your audio files in order to enable Amazon Translate functionality.

= What is Amazon CloudFront? =

Amazon CloudFront is a web service that speeds up distribution of your static and dynamic web content, such as .html, .css, .js, and image files, to your users. CloudFront delivers your content through a worldwide network of data centers called edge locations. When a user requests content that you're serving with CloudFront, the user is routed to the edge location that provides the lowest latency so that content is delivered with the best possible performance.

= How much does Amazon CloudFront cost? =

For current pricing information, see the Amazon CloudFront pricing page at https://aws.amazon.com/cloudfront/pricing/.

= Does Amazon CloudFront participate in the AWS Free Tier? =

Yes. As part of the AWS Free Usage Tier (https://aws.amazon.com/free/), you can get started with Amazon CloudFront for free. Upon sign-up, new Amazon CloudFront customers get 50 gigabytes of data transfer out and 2 million viewer requests for free each month for the first 12 months.

== Screenshots ==

1. The configuration settings page for the plugin.
2. When generating text-based content, you can also produce an audio version of the same content by activating Amazon Polly.
3. After activating the plugin, each audio section will have its own play button, which will allow the end user to listen to the content.

== Changelog ==

= 4.2.2 =
* Code Refactoring

= 4.2.1 =
* Code Refactoring

= 4.2.0 =
* Code Refactoring

= 4.0.3 =
* Bug Fixing.

= 4.0.2 =
* Added translate support for multiple new languages
* Added Conversational Polly Style.

= 4.0.1 =
* Bug Fixing.

= 4.0.0 =
* Rebranding to "AWS for WordPress".
* Adding CloudFront (CDN) functionality.

= 3.1.2-3 =
* Bug Fixing.

= 3.1.1-5 =
* Bug Fixing.

= 3.1.1 =
* Added support for Text-to-Speech Icelandic language

= 3.1.0 =
* New "Neutral" engine for text-to-speech functionality added.
* New "Newscaster" voice added.
* Code refactoring

= 3.0.6 =
* Added translate support for Hindi, Farsi, Malay, and Norwegian languages.

= 3.0.5 =
* Added support for Arabic Language
* Bug Fixing

= 3.0.3 =
* Added detailed logging

= 3.0.2 =
* Added Podcast Title and Description in options to customize the feed.

= 3.0.1 =
* Bug Fixing.

= 3.0.0 =
* Alexa Integration added.

= 2.6.4 =
* Bug fixing.
* Plugin Renaming.

= 2.6.3 =
* Added possibility to specify combination of label and flag to be displayed with translations.
* Added possibility to add 'Subscribe' Button on the page
* Added possibility to disable 'download' button for audio files.

= 2.6.2 =
* Added possibility to specify tags, which won't be read (for example 'audio'). This option is available under 'Text-To-Speech' tab as 'Skip tags' option.
* Cleaning WordPress options when plugin is uninstalled.

= 2.6.1 =
* Added support for 8 new languages for translate functionality.
* Changed the way how audio is being generate (background process).
* Added a way of enabling plugin logging.

= 2.6.0 =
* Fix problem with media library.

= 2.5.7 =
* Bug fixing.

= 2.5.5 =
* Added possibility of converting Chinese text to audio.
* Added possibility to specify label instead of flag when translating text.
* Added possibility to specify podcast author.
* Bug fixing.

= 2.5.1-4 =
* Bug fixing.

= 2.5.0 =
* Bug fixing.
* Redesign GUI.

= 2.0.5 =
* Added possibility to use HTTPS in RSS Feed.

= 2.0.4 =
* Added possibility to specify category of posts to be displayed in RSS feed.
* Change way creating Amazon PollyCast description field.

= 2.0.3 =
* Adding possibility to specify RSS feed size.
* Bug fixing.

= 2.0.2 =
* Enabling plugin to be invoked with by quick edit.
* Respecting  uploads_use_yearmonth_folders param.
* Bug fixing.

= 2.0.1 =
* IMPORTANT: YOU NEED TO UPDATE IAM Policy based on new template.
* Added integration with Amazon Translate, which enables translating posts/audio in other languages.
* Added support for Lexicons.
* Added support for providing excerpt of the post to audio.
* Bug fixing.

= 1.0.11 =
* Modified according to new Amazon Polly limits for single text conversion (1500 -> 3000 characters).
* Modified the logic for presenting “Voiced by" image.
* Bug fixing.

= 1.0.10 =
* Modified according to new Amazon Polly limits for single text conversion (3000 -> 6000 characters).
* Modified the logic for presenting "Power by" image.
* Bug fixing.

= 1.0.9 =
* Bug fixing

= 1.0.8 =
* Bug fixing

= 1.0.7 =
* Added possibility to enable adding breathing sounds to audio files.
* Added possibility to enable/disable adding post's title to audio file.
* Added possibility to specify post type in GUI.
* Added possibility to disable podcast functionality.
* Added support for SSML break tag.

= 1.0.6 =
* Added new filter, which let to specify S3 bucket name where files will be stored.

= 1.0.5 =
* License change to GPLv3
* Added possibility of changing speed of generated audio files.
* Fixing problems with 3rd party libraries.

= 1.0.4 =
* Bug fixes

= 1.0.3 =
* IMPORTANT: YOU NEED TO UPDATE IAM Policy based on new template.
* Add "Audio Only" functionality.
* Add "Words Only" functionality.
* Add possibility of changing AWS region.
* Add possibility to add player label.
* Updates logic for estimating the total cost of bulk update.
* Updates the branding of the player (text changed to image).

= 1.0.2 =
* Updates percentage done calculation during bulk updates.
* Updates upload directory creation method.
* Updates location where ‘Voiced by Amazon Polly’ is being shown (only on singular page view)

= 1.0.1 =
* Fix the issue with converting special characters.

= 1.0.0 =
* Release of the plugin
