=== Amazon Polly for WordPress ===
Contributors: awslabs, wpengine, tstachlewski, stevenkword
Tags: AWS, Amazon Web Services, WP Engine, Cloud, Text-to-Speech, Amazon Polly
Requires at least: 3.0.1
Requires PHP: 5.6
Tested up to: 4.9
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Amazon Polly is a service that turns text into lifelike speech. With dozens of voices across a variety of languages, you can select the ideal voice and build engaging speech-enabled applications that work in many different countries. The Amazon Polly plugin for WordPress is a sample application that shows how WordPress creators can easily add Text-to-Speech capabilities to written content with Amazon Polly. You can generate an audio feed for text-based content and insert it into an embedded player to increase the accessibility of your WordPress site. The sample code also enables you to publish podcasts directly from your site and make them available for listeners in the form of podcasts.

== Installation ==

1. Install this plugin from the ‘Add new Plugin’ option from your WordPress installation.
2. After you install (and activate) the plugin, please go to the ‘Amazon Polly’ settings tab in your WordPress admin interface and fill out the configuration settings for your plugin. You will need to have the Access Key and Secret Key to your own AWS account in order to finish the configuration of the plugin - you can find instructions about obtaining those below.
2.1 If you deployed your WordPress on EC2, you can also use the functionality of IAM Roles for EC2 - in this case you won’t need to provide the keys on the configuration page. For more details please visit: http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/iam-roles-for-amazon-ec2.html

Additional configuration options which you can change:

General:
- Sample rate: The audio frequency specified in Hz.
- Voice name: The voice which should be used to create audio.
- Player position: Position of the ‘play’ button on your WordPress page. (Below or After the post, or no button)
- New post default: Define if transcription should be enabled by default for new posts.
- Autoplay: Information if the audio should be played automatically on ’singular’ post page.

Cloud Storage:
- Store audio in Amazon S3: If you will select this option, audio files won’t be stored on WordPress server, but instead they will be stored on Amazon S3 service. For additional information and pricing, please visit: https://aws.amazon.com/s3.
- Amazon CloudFront (CDN) Domain Name: The name of you CloudFront domain, which should be used to stream audio. You will need to create it first in AWS, and then provide it’s name here.


Amazon Pollycast:
- iTunes category: Category of your podcast.
- iTunes explicit: Define if podcast functionality should be created for your posts.
- iTunes image: Icon for your iTunes podcast channel.
- iTunes email: The editorial contact for the podcast channel.

Additional Configuration
- Bulk update all posts: Update all posts with new configurations: If you will click on the button, the plugin will update all your posts according to your new plugin configuration.


How to obtain Access Key and Secret Key?
1. We assume, that you have got already AWS Account. If not, please got to https://aws.amazon.com/ and create one.
2. Sign in to the AWS Console from https://aws.amazon.com/ page.
3. Type “IAM” in the search field, after click on it, you will be redirect to IAM (Identity and Access Management service).
4. On the left menu, look for the ‘Policies’ label - click on it. You will see a list of already created policies, we will create new one (“Create Policy” - button).
5. Choose “JSON” view, and paste following code there:
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
                "polly:DescribeVoices"
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
            "Resource": "arn:aws:s3:::audio_for_wordpress*"
        }
    ]
}
```
6. On the second step of the wizard provide the name for your policy: PollyForWordPressPolicy and create it.
7. The next step will be to create a IAM User. To do this, on the left menu choose ‘Users’ label. There click on ‘Add User’ button.
8. Provide a name for your user, for example: “WordPress” and select “Programmatic access” option. Go to the step number 2.
9. On the “Permission” step, choose ‘Attach existing policies directly’ option, and then on the list provide the name of the policy which we have created in step 6 (PollyForWordPressPolicy).
10. On the “Review” step click on “Create User” button.
11. On step, you will see information that the user was created. You need to note “Access key ID” and “Secret access Key” - you will need to provide them on the plugin configuration page.

== Frequently Asked Questions ==

= Do I need to have AWS account to be able to start using the plugin? =

Yes. You can create your own AWS account here: https://aws.amazon.com/free/

= What is Amazon Polly? =

Amazon Polly is a service that turns text into lifelike speech. Amazon Polly enables existing applications to speak as a first class feature and creates the opportunity for entirely new categories of speech-enabled products, from mobile apps and cars, to devices and appliances. Amazon Polly includes dozens of lifelike voices and support for multiple languages, so you can select the ideal voice and distribute your speech-enabled applications in many geographies. Amazon Polly is easy to use – you just send the text you want converted into speech to the Amazon Polly API, and Amazon Polly immediately returns the audio stream to your application so you can play it directly or store it in a standard audio file format, such as MP3. Amazon Polly supports Speech Synthesis Markup Language (SSML) tags like prosody so you can adjust the speech rate, pitch, or volume. Amazon Polly is a secure service that delivers all of these benefits at high scale and at low latency. You can cache and replay Amazon Polly’s generated speech at no additional cost. Amazon Polly lets you convert 5M characters per month for free during the first year, upon sign-up. Amazon Polly’s pay-as-you-go pricing, low cost per request, and lack of restrictions on storage and reuse of voice output make it a cost-effective way to enable speech synthesis everywhere.

= Which languages are supported? =

English (American), English (Australian), English (British), English (Indian), English (Welsh), Welsh, Danish, Dutch, French, French (Canadian), German, Icelandic, Italian, Japanese, Korean, Polish, Portuguese, Portuguese (Brazilian), Romanian, Russian, Spanish (Castilian), Spanish (American), Swedish, Turkish, Norwegian

= How much does Amazon Polly cost? =

Please see the Amazon Polly Pricing Page (https://aws.amazon.com/polly/pricing/) for current pricing information.

= Does Amazon Polly participate in the AWS Free Tier? =

Yes, as part of the AWS Free Usage Tier (https://aws.amazon.com/free/), you can get started with Amazon Polly for free. Upon sign-up, new Amazon Polly customers can synthesize up to 5M characters for free each month for the first 12 months.

= Does plugin delete my audio files if I will delete the plugin? =

No. All audio files are being preserved. Depending on your configuration, they will be stored on your WordPress server, or on your Amazon S3 bucket.

= Is there additional price for storing audio files on S3? =

Amazon S3 (Simple Storage Service) has got it’s own pricing, you can find information here: https://aws.amazon.com/s3/pricing/

= How do I view my Amazon Pollycast feed? =

Attach '/amazon-pollycast/' to any page URL.

example.com/amazon-pollycast/
example.com/category/news/amazon-pollycast/
example.com/author/john/amazon-pollycast/

= How do I publish my podcast with iTunes? =

Submit your Amazon PollyCast to the iTunes iConnect directory: https://itunesconnect.apple.com

== Screenshots ==

1. The view of the configuration settings page for the plugin.
2. When generating text-based content, the user can also produce a voiced version of the same content by activating Amazon Polly.
3. After activation of the plugin, each voiced section will have its own play button, which will allow the end user to listen to the content.

== Changelog ==

= 1.0.2 =
* Updates percentage done calculation during bulk updates.
* Updates upload directory creation method.
* Updates location where ‘Voiced by Amazon Polly’ is being shown (only on singular page view)

= 1.0.1 =
* Fix the issue with converting special characters.

= 1.0.0 =
* Release of the plugin
