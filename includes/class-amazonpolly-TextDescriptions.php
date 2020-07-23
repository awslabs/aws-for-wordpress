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
$desc_text_amazon_cf = 'Amazon CloudFront is a content delivery network (CDN) that has a network of hundreds of <a href="https://aws.amazon.com/cloudfront/features">edge locations</a> around the world. As visitors come to your website, CloudFront pulls content from your origin web server and caches it in edge locations closer to your visitors to provide a faster and more reliable viewing experience. See the CloudFront <a href="https://aws.amazon.com/cloudfront/pricing">pricing page</a> for more information about CloudFront\'s Free Tier offering and on-demand pricing.';

$desc_text_amazon_cf_origin = '<i>The DNS domain name of the origin web server where CloudFront will get your website\'s content. For WordPress.com hosting services, this is the domain name associated with your WordPress account, such as example.com.</i>';

$desc_text_amazon_cf_alias = '<i>Choose the domain name for delivering your website content through CloudFront. This is the domain name that your visitors will use for the accelerated website experience.<br>
	We recommend using \'www\' in front of the domain. For WordPress.com hosting, this will be a subdomain of your origin domain, such as www.example.com.</i>';

$desc_text_initiate_setup = 'To set up your CloudFront distribution, the plugin first creates an SSL/TLS certificate, which is required to use an alternate domain name with CloudFront and secure your visitors\' connections to your website. After CloudFront validates the certificate, the plugin sets up a distribution optimized for your WordPress website. For more information about how the plugin works, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_validate_acm_cert = 'To enable CloudFront to validate the SSL/TLS certificate, add the following record to your DNS records. For more information about updating your WordPress.com DNS records with a custom entry, see the <a href="https://en.support.wordpress.com/domains/custom-dns/">WordPress.com Support website</a>. For more information about SSL/TLS certificate DNS validation, see the AWS Certificate Manager <a href="https://docs.aws.amazon.com/acm/latest/userguide/gs-acm-validate-dns.html">documentation</a>.';

$desc_text_cdn_alias_dns = 'To start sending your website traffic to your CloudFront alternate domain name, add the following record to your DNS records. For more information about updating your WordPress.com DNS records with a custom entry, see the <a href="https://en.support.wordpress.com/domains/custom-dns/">WordPress.com Support website</a>.';

$desc_text_remove_site_acceleration = 'If you remove site acceleration, CloudFront will delete your distribution. Before you proceed, make sure that you correctly update your DNS records to avoid any disruption to your website traffic. For more information about how to properly remove site acceleration, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_stack_ready = 'Your website is now ready to accelerate content through CloudFront. To activate site acceleration, select the check box, and then choose <b>Save Changes</b>. For more information about how the plugin works, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_stack_deletion_in_progress = 'CloudFront is deleting the distribution that was created for your WordPress website.<br> Make sure that your DNS records are properly updated to avoid any disruption to your website traffic. For more information, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>.';

$desc_text_cf_in_progress = 'Your SSL/TLS certificate has been successfully created and validated. CloudFront is now creating a distribution that is optimized for your WordPress website. For more information, see the <a href="https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WordPressPlugin.html">CloudFront documentation</a>. It can take several minutes for the distribution to deploy globally.';