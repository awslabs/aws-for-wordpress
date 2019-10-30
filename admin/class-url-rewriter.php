<?php

/**
 * This file defines the URL Rewriter class
 *
 *
 * @link       amazon.com
 * @since      4.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_UrlRewriter
{
    /**
    * Function to add Wordpress hooks for which content rendered by Wordpress is rewritten
     *
     *
     * @since           4.0.0
     */
    function define_url_rewrite_hooks() {
        add_filter('the_content',array($this,'rewrite_urls'),100);
        add_filter('post_thumbnail_html',array($this,'rewrite_urls'),100);
        add_filter('widget_text',array($this,'rewrite_urls'),100);
        add_filter('wp_get_attachment_link', array($this,'rewrite_urls'),100);
        add_filter('theme_root_uri',array($this,'rewrite_urls'),100);
        add_filter('plugins_url',array($this,'rewrite_urls'),100);
        add_filter('widget_text',array($this,'rewrite_urls'),100);
        add_filter('wp_get_attachment_link',array($this,'rewrite_urls'),100);
        add_filter('wp_get_attachment_thumb_file',array($this,'rewrite_urls'),100);
        add_filter('wp_get_attachment_thumb_url',array($this,'rewrite_urls'),100);
        add_filter('wp_get_attachment_url',array($this,'rewrite_urls'),100);
        add_filter('post_gallery',array($this,'rewrite_urls'),100);
        add_filter('bloginfo', array($this,'rewrite_urls'),100);
        add_filter('style_loader_src',array($this,'rewrite_urls'),100);
        add_filter('script_loader_src',array($this,'rewrite_urls'),100);
        add_filter('metaslider_resized_image_url',array($this,'rewrite_urls'),100);
    }

    /**
     * Replaces site URL with the alternate URL (CDN)
     * (Adopted from AWS CDN by WPAdmin)
     *
     * @param           string $content The content rendered by Wordpress which is rewritten to replace embedded URLs
     * @since           4.0.0
     */
    function rewrite_urls($content) {
        $olddomain = get_option('siteurl');
        $olddomain = preg_replace('#^https?://#', '', $olddomain);
        $newdomain = get_option('amazon_cf_alias');
        $newdomain = preg_replace('#^https?://#', '', $newdomain);

        if (is_admin_bar_showing()) {
            return $content;
        }
        if (stripos($_SERVER['REQUEST_URI'],".xml") == NULL) {
            $serverproto = "http";
            if (@$_SERVER['HTTPS'] == "on") {
                $serverproto = "https";
            }

            $old = $serverproto . "://" . $olddomain;
            $new = $newdomain;
            if ($new != "") {
                $new = $serverproto . "://" . $newdomain;
                $content = str_replace($old, $new, $content);
            }

        }
        return $content;
    }
}