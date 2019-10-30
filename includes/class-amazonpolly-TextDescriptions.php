<?php
/**
 * The file that defines text descriptions
 *
 * @link       amazon.com
 * @since      4.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 */
$desc_text_amazon_cf = 'Amazon CloudFront is a content delivery network (CDN) that has a network of hundreds of <a href="https://aws.amazon.com/cloudfront/features">Edge locations</a> around the world. As visitors come to your site, CloudFront pulls content from your origin server and caches it in Edge locations closer to your visitors to provide a faster and a more reliable viewing experience. See the CloudFront <a href="https://aws.amazon.com/cloudfront/pricing">pricing page</a> for details about CloudFront\'s Free Tier offering and on-demand pricing.';

$desc_text_amazon_cf_origin = '<i>The DNS domain of the HTTP origin server where CloudFront will get your website\'s content. For WordPress.com hosting services, this is the domain name associated with your WordPress account, such as example.com.</i>';

$desc_text_amazon_cf_alias = '<i>Choose the domain name for delivering your website content through CloudFront. This is the domain name that your visitors will use for the accelerated website experience.<br>
	We recommend using \'www\' in front of the domain. For WordPress.com hosting, this will be a subdomain of your origin domain, such as www.example.com.</i>';

$desc_text_initiate_setup = 'To set up your CloudFront distribution, the plugin first creates an SSL certificate, which is required to use an alternate domain name with CloudFront and secure your visitors\' connections to your website. After  CloudFront validates the certificate, the plugin sets up a distribution optimized for your WordPress website. For more information about how the plugin works, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_validate_acm_cert = 'To enable CloudFront to validate the SSL certificate, update your DNS records with the following record. You can find an example on how to update your WordPress DNS records with a custom entry <a href="https://en.support.wordpress.com/domains/custom-dns/">here</a>. For more information about SSL certificate DNS validation, see the AWS Certificate Manager <a href="https://docs.aws.amazon.com/acm/latest/userguide/gs-acm-validate-dns.html">documentation</a>.';

$desc_text_cdn_alias_dns = 'To start sending your web traffic to your CloudFront alternate domain name, update your DNS records with the following record. You can find an example on how to update your WordPress DNS records with a custom entry <a href="https://en.support.wordpress.com/domains/custom-dns/">here</a>.';

$desc_text_remove_site_acceleration = 'If you remove site acceleration, CloudFront will delete your distribution. Before you proceed, it\'s important that you correctly update your DNS records to avoid any disruption to your web traffic. For more information on how to properly remove site acceleration, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_stack_ready = 'Your website is now ready to accelerate content through CloudFront! Just select the checkbox and then choose Save Changes. For more information about how the plugin works, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_stack_deletion_in_progress = 'CloudFront is deleting the distribution that was created for your WordPress website.<br> Confirm that your DNS records are properly updated to avoid any disruption to your web traffic. For more information, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_cf_in_progress = 'Your SSL certificate has been successfully created and validated. CloudFront is now setting up a distribution that is optimized for your WordPress website. To learn more about these configurations, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>. It can take several minutes to deploy globally.';