=== Amazon Polly for WordPress ===
Contributors: awslabs, wpengine, tstachlewski, stevenkword
Tags: AWS, Amazon Web Services, WP Engine, Cloud, Text-to-Speech, Amazon Polly
Requires at least: 3.0.1
Requires PHP: 5.6
Tested up to: 4.9
Stable tag: 2.0.5
License: GPLv3 ONLY
License URI: https://www.gnu.org/licenses/gpl-3.0.html


== Description ==

Create audio version of your posts, translate them into other languages and create podcasts! Amazon Polly is a service that turns text into lifelike speech. With dozens of voices across a variety of languages, you can select the ideal voice and build engaging speech-enabled applications that work in many different countries. The Amazon Polly plugin for WordPress is a sample application that shows how WordPress creators can easily add Text-to-Speech capabilities to written content with Amazon Polly. You can generate an audio feed for text-based content and insert it into an embedded player to increase the accessibility of your WordPress site. The sample code also enables you to publish podcasts directly from your site and make them available for listeners in the form of podcasts. In addition, the plugin allows to translate your text from one language to another using Amazon Translate– which is a neural machine translation service that delivers fast, high-quality, and affordable language translation. Amazon Translate allows you to localize content - such as websites and applications - for international users, and to easily translate large volumes of text efficiently.

== Installation ==

1. Install this plugin from the ‘Add new Plugin’ option from your WordPress installation.
2. After you install (and activate) the plugin, go to the ‘Amazon Polly Settings’ tab in your WordPress admin interface and configure the settings for your plugin. You will need to have the Access Key and Secret Key to your own AWS account in order to finish the configuration of the plugin - you can find instructions about obtaining those below.
2.1 If you deployed WordPress on EC2, you can also use the IAM Roles functionality for the pluin - in this case you won’t need to provide the keys on the configuration page. For more details please visit: http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/iam-roles-for-amazon-ec2.html.

== Configuration ==
The list below presents configurations options which can be modified by the user.

General:
- AWS access key: the access key which is required to connect to AWS.
- AWS secret key: the secret key which is required to connect to AWS.
- AWS Region: The AWS region which will be used to convert content into audio and to store the data (if S3 storage is selected). In most cases, you should choose the region closest to your readers.

Amazon Polly settings:
- Sample rate:  The sample rate of the audio files that will be generated, in Hz. Higher sampling rates mean higher quality audio.
- Voice name: The Amazon Polly voice to use to create the audio file.
- Automated breaths: If enabled, Amazon Polly automatically creates breathing noises at appropriate intervals.
- Enable SSML Support: If enabled, you can add SSML tags to your post content.
- Audio speed: The speed at which your audio files are recorded, proportional to the natural speed of your preferred voice.  100% is the default value; minimum is 20% and maximum is 200%.
- Lexicons: The names of lexicons used for audio generation. These lexicons must already be uploaded to your AWS account to be used.

Player settings:
- Player position: The position of the audio player on your WordPress page. You can put it before or after the post, or not use it at all. If you want to make your files available using Amazon Pollycast, choose to not display the audio player.
- Player Label: Specifies optional text that will be shown above the audio player. HTML tags are supported with this label.
- New post default: Specifies whether Amazon Polly is automatically enabled for all new posts. Choose this option if you want Amazon Polly to use the configuration settings to create an audio file for each new post.
- Autoplay: Specifies whether the audio player automatically starts playing the audio when a user visits an individual post on the website.
- Feed size: Number of items (posts/pages) which will be shown in RSS feed. Max value is 1000.

Cloud Storage:
- Store audio in Amazon S3: If you want to store audio files using the Amazon S3 service instead of on the WordPress server, choose this option. For additional information and pricing, see: https://aws.amazon.com/s3.
- Amazon CloudFront (CDN) domain name: If you want to broadcast your audio files with Amazon CloudFront, provide the name of your CloudFront domain, which the plugin will use for streaming audio. You must first create the domain in Amazon CloudFront

Amazon Pollycast:
- Podcast enabled: If enabled, the Amazon Polly WordPress plugin will create an XML feed that can then be consumed by ITunes or other applications to generate a podcast.
- iTunes category: The category for your podcast. Choosing a category makes it easier for podcast users to find your podcast in the ITunes podcast catalog.
- iTunes explicit: Specifies whether to the Amazon Pollycast contains explicit content.
- iTunes image: Specifies the icon uploaded for the podcast.
- iTunes email: Specifies the contact email for the podcast with ITunes.
- Feed size: Number of items (posts/pages) which will be shown in RSS feed. Max value is 1000.
- Post category: category of posts which will be added to RSS feed. If field is empty, all posts will be added. You can specify multiple category, separated by comma. Example of values: "personal", "personal,business"
- Use HTTPS for audio files: Specify if HTTPS protocol should be used in links to audio files in RSS Feed.

Additional Configuration
- Bulk update: Specifies whether you want to bulk update all posts to use new plugin settings.The bulk update functionality doesn't use translate functionality of the plugin.
- Add post title to audio: If enabled, each audio file will start from post title.
- Add post excerpt to audio: If enabled, each audio file will have an excerpt from the post at the beginning of the audio.
- Post Types: Specifies which type of WordPress page will be converted to audio files.

Amazon Translate configuration
- Enable translation support: Specifies whether the content translation functionality will be enabled.
- Show transcript: If enabled, reader will be able to see the translated transcript of the post in other languages.
- Source language: Specifies the original source language in which the content was written.
- Source language label: Defines a label for the original source language.
- Translations label: Specifies the label which will be shown next to available languages.
- Target languages: Defines a list of the available languages into which the WordPress content can be translated (and for which the audio will also be generated).

== How to obtain AWS Access Key and Secret Key? ==
1. If you don't already have an AWS Account, go to https://aws.amazon.com/ to create one.
2. Sign in to the AWS Console at https://aws.amazon.com/.
3. Type “IAM” in the search field, choose it, and you will be redirected to the Identity and Access Management service (IAM).
4. On the left menu, choose 'Policies' and then then choose 'Create Policy'.
5. On the JSON tab, paste the following code:
```
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "Permissions1",
            "Effect": "Allow",
            "Action": [
                "s3:HeadBucket",
                "polly:SynthesizeSpeech",
                "polly:DescribeVoices",
                "translate:TranslateText"
            ],
            "Resource": "*"
        },
        {
            "Sid": "Permissions2",
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket",
                "s3:GetBucketAcl",
                "s3:GetBucketPolicy",
                "s3:PutObject",
		"s3:DeleteObject",
                "s3:CreateBucket",
                "s3:PutObjectAcl"
            ],
            "Resource": ["arn:aws:s3:::audio_for_wordpress*","arn:aws:s3:::audio-for-wordpress*"]
        }
    ]
}
```
6. Choose 'Review Policy'.
7. Provide the name for your policy:  PollyForWordPressPolicy, and then choose 'Create Policy'.
7. From the left menu choose ‘Users’ and then choose 'Add User'.
8. Provide the name for your user, for example: WordPress. Under Access Type, choose 'Programmatic access'. Choose 'Next: Permissions'.
9. Choose ‘Attach existing policies directly’.  From the list of policies, choose PollyforWordPressPolicy, and then choose 'Next: Review'.
10. Choose 'Create User'.
11. Make note of the 'Access key ID' and the 'Secret access Key because you will need to provide them on the WordPress plugin configuration page. If you do not copy them now, you will not be able to access them later.


== Frequently Asked Questions ==

= Do I need to have AWS account to be able to start using the plugin? =

Yes. You can create your own AWS account here: https://aws.amazon.com/free/

= What is Amazon Polly? =

Amazon Polly is a service that turns text into lifelike speech. Amazon Polly enables existing applications to speak as a first class feature and creates the opportunity for entirely new categories of speech-enabled products, from mobile apps and cars, to devices and appliances. Amazon Polly includes dozens of lifelike voices and support for multiple languages, so you can select the ideal voice and distribute your speech-enabled applications in many locations. Amazon Polly is easy to use – you simply send the text you want converted into speech to the Amazon Polly API, and Amazon Polly immediately returns the audio stream to your application so you can either play it directly or store it in a standard audio file format, such as MP3. Amazon Polly supports Speech Synthesis Markup Language (SSML) tags  so you can add breathing sounds, add pauses, and adjust the speech rate, pitch, or volume. Amazon Polly is a secure service that delivers all of these benefits at high scale and at low latency. You can save and replay Amazon Polly’s generated speech at no additional cost. Amazon Polly lets you convert 5M characters per month for free during the first year, upon sign-up. Amazon Polly’s pay-as-you-go pricing, low cost per request, and lack of restrictions on storage and reuse of voice output make it a cost-effective way to enable speech synthesis everywhere.

= Which languages are supported? =

Danish, Dutch, English (US), English (Australian), English (British), English (Indian), English (Welsh), French, French (Canadian), German, Icelandic, Italian, Japanese, Korean, Polish, Portuguese, Portuguese (Brazilian), Romanian, Russian, Spanish (European), Spanish (American), Swedish, Turkish, Norwegian, and Welsh.

= What is Amazon Translate? =

Amazon Translate is a Neural Machine Translation (MT) service for translating text between supported languages. Powered by deep learning methods, the service provides high quality and affordable machine translation, enabling developers to translate company and user-authored content, or build applications requiring support across multiple languages. The service can be used via an API, enabling real-time translation of text from the source language to the target language.

= How much does Amazon Polly cost? =

Please see the Amazon Polly Pricing Page (https://aws.amazon.com/polly/pricing/) for current pricing information.

= Does Amazon Polly participate in the AWS Free Tier? =

Yes. As part of the AWS Free Usage Tier (https://aws.amazon.com/free/), you can get started with Amazon Polly for free. Upon sign-up, new Amazon Polly customers can synthesize up to 5M characters for free each month for the first 12 months.

= How much does Amazon Translate cost? =

Please see the Amazon Translate Pricing Page (https://aws.amazon.com/translate/pricing/) for current pricing information.

= Does Amazon Translate participate in the AWS Free Tier? =

Yes. As part of the AWS Free Usage Tier (https://aws.amazon.com/free/), you can get started with Amazon Translate for free. Upon sign-up, new Amazon Translate customers can translate up to 2M characters for free each month for the first 12 months.

= Which languages can I use with Amazon Translate? =

You can translate your content between following languages: English, Spanish, French, German, and Portuguese. If your content is in other languages, you won't be able to translate it using Amazon Translate.

= If I enabled Amazon Translate functionality, will my post be translated automatically? =

No. To translate the post, you must first publish the post, and then enable the Amazon Translate functionality for this specific post by choosing the Translate button.

= Can I use Amazon Translate if I want to store files on the WordPress server? =

No. You must enable Amazon S3 as the default storage location for your audio files in order to enable Amazon Translate functionality.

= Does plugin delete my audio files if I will delete the plugin? =

No. All audio files are being preserved. Depending on your configuration, they will be stored on your WordPress server, or on your Amazon S3 bucket.

= Does the plugin support SSML tags? =

Amazon Polly itself support multiple SSML tags (You can find more about them here: https://docs.aws.amazon.com/polly/latest/dg/ssml.html).

The Amazon Polly Plugin currently supports only the <break> SSML tag (you can find more about it at this page: https://docs.aws.amazon.com/polly/latest/dg/supported-ssml.html).

To use SSML tags, you will need to enable Amazon S3 as the storage location for your files and enable SSML support in the plugin configuration page. In the wizard for creating a new WordPress post, you will be able to add ssml tags. An example of content with the SSML break tag is:

Mary had a little lamb <ssml><break time="3s"/></ssml> whose fleece was white as snow.

= Is there additional price for storing audio files on S3? =

Amazon S3 (Simple Storage Service) has got its own pricing, you can find information here: https://aws.amazon.com/s3/pricing/

= How do I view my Amazon Pollycast feed? =

Attach '/amazon-pollycast/' to any page URL.

* example.com/feed/amazon-pollycast/
* example.com/category/news/feed/amazon-pollycast/
* example.com/author/john/feed/amazon-pollycast/

= How do I publish my podcast with iTunes? =

Submit your Amazon PollyCast to the iTunes iConnect directory: https://podcastsconnect.apple.com/

= How is the bulk update cost calculated? =

If you bulk update less than 100 posts, the plugin will calculate the number of characters in all of the posts and, based on Amazon Polly pricing, it will provide an estimate for the cost of conversion. If you have more than 100 posts, the plugin will calculate the average number of characters in the first 100 posts, and then, based on this, estimate the total number of characters in all posts.

= What kind of filters can I use? =

The plugin has a couple of different filters, which can be used by developers and WordPress users to modify its behavior.

Available filters:
-amazon_polly_post_types - specifies what kind of WordPress post types should be used by the plugin. The default value is 'post'.
-amazon_polly_content - enables you to modify the content of the post before it will is sent to Amazon Polly service for text-to-speech conversion.
- amazon_polly_s3_bucket_name - enables you to define your own bucket name where audio files will be stored. The bucket must already exist, and should be in the same region as you specify in the plugin configuration. You need to modify also the IAM policy to provide access to this bucket.

= What are lexicons? =
Pronunciation lexicons enable you to customize the pronunciation of words. In the plugin configuration, you can provide the names of the lexicons which you have previously uploaded to your AWS account in the region being used. Up to five lexicons can be used when separated by spaces.

== Screenshots ==

1. The view of the configuration settings page for the plugin.
2. When generating text-based content, the user can also produce a voiced version of the same content by activating Amazon Polly.
3. After activation of the plugin, each voiced section will have its own play button, which will allow the end user to listen to the content.

== What is "Audio Only" functionality? ==

If you want to add something to the audio but you don't want to display it in the browser, you use the -AMAZONPOLLY-ONLYAUDIO- tag. For example:

-AMAZONPOLLY-ONLYAUDIO-START-

[Text that will be read by the plugin but not shown in the browser.]

-AMAZONPOLLY-ONLYAUDIO-END-

Important: Text will be shown in transcript if translate functionality is enabled, even if it was marked as "Audio Only".

== What is "Words Only" functionality? ==

If you have a piece of content that you want on your page but not read by the plugin, you use the -AMAZONPOLLY-ONLYWORDS- tag. For example:

-AMAZONPOLLY-ONLYWORDS-START-

[Text that will be shown in the browser but not read by the plugin.]

-AMAZONPOLLY-ONLYWORDS-END-

Important: Text will be read in translated audio files if translate functionality is enabled, even if it was marked as "Words Only".

== Changelog ==

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
