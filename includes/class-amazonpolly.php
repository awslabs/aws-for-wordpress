<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       amazon.com
 * @since      1.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 * @author     AWS Labs
 */
class Amazonpolly {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Amazonpolly_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'amazonpolly';
		$this->version     = '1.0.0';
		$this->load_dependencies();

		$this->common      = new AmazonAI_Common();
		$this->common->init();

		$this->define_global_hooks();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Amazonpolly_Loader. Orchestrates the hooks of the plugin.
	 * - Amazonpolly_I18n. Defines internationalization functionality.
	 * - Amazonpolly_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-amazonpolly-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-amazonpolly-i18n.php';

		/**
		 * Misc. classes responsible for helping with admin requests
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-PostMetaBox.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-BackgroundTask.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-Exceptions.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-Translator.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-FileHandler.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-LocalFileHandler.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-S3FileHandler.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-Logger.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-Common.php';

		/**
		 * Classes responsible for admin setting pages
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-AlexaConfiguration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-CloudFrontConfiguration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-GeneralConfiguration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-PollyService.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-PodcastConfiguration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-PollyConfiguration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-TranslateConfiguration.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-amazonpolly-public.php';

		/**
		 * Class responsible for CloudFormation and associated resources
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/AmazonAI-Cloudformation.php';

		/**
		 * This class is responsible for rewriting URLs
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-url-rewriter.php';

		/**
		 * The class responsible for creating the podcast feature.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-amazonpolly-pollycast.php';

		/**
		 * Load AWS PHP SDK
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

		/**
		 * The class responsible for custom helper functions
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-helper.php';

		$this->loader = new Amazonpolly_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Amazonpolly_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Amazonpolly_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * @return mixed
	 */
	private function define_global_hooks()
	{
		add_filter('amazon_polly_logging_enabled', [ $this->common, 'is_logging_enabled' ]);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

        $background_task = new AmazonAI_BackgroundTask();
        $cloudformation_service = new AmazonAI_Cloudformation($this->common);
        $general_configuration = new AmazonAI_GeneralConfiguration($this->common);
        $polly_configuration = new AmazonAI_PollyConfiguration($this->common);
        $translate_configuration = new AmazonAI_TranslateConfiguration($this->common);
        $podcast_configuration = new AmazonAI_PodcastConfiguration($this->common);
        $alexa_configuration = new AmazonAI_AlexaConfiguration($this->common);
        $cloudfront_configuration = new AmazonAI_CloudFrontConfiguration($this->common, $cloudformation_service);
        $polly_service = new AmazonAI_PollyService($this->common);
        $translate_service = new AmazonAI_Translator($this->common);

        $plugin_name = get_option('amazon_plugin_name');
        $this->loader->add_filter( "plugin_action_links_$plugin_name", $this->common, 'add_settings_link');

        $this->loader->add_action( sprintf('admin_post_%s', AmazonAI_BackgroundTask::ADMIN_POST_ACTION), $background_task, 'run');

        $this->loader->add_action( 'admin_print_footer_scripts', $this->common, 'add_quicktags');
        $this->loader->add_action( 'admin_enqueue_scripts', $this->common, 'enqueue_styles');
        $this->loader->add_action( 'admin_enqueue_scripts', $this->common, 'enqueue_scripts');
        $this->loader->add_action( 'admin_enqueue_scripts', $this->common, 'enqueue_custom_scripts');
        $this->loader->add_action( 'add_meta_boxes', $this->common, 'field_checkbox');
        $this->loader->add_action( 'save_post', $polly_service, 'save_post', 10, 3);
        $this->loader->add_action( 'amazon_polly_background_task_generate_post_audio', $polly_service, 'generate_audio', 10, 3);


		$this->loader->add_action( 'before_delete_post', $this->common, 'delete_post' );
		$this->loader->add_action( 'wp_ajax_polly_transcribe', $polly_service, 'ajax_bulk_synthesize' );
		$this->loader->add_action( 'wp_ajax_polly_translate', $translate_service, 'ajax_translate' );

		$this->loader->add_action( 'admin_menu', $general_configuration, 'amazon_ai_add_menu' );
		$this->loader->add_action( 'admin_init', $general_configuration, 'display_options' );

		$this->loader->add_action( 'admin_menu', $polly_configuration, 'amazon_ai_add_menu' );
		$this->loader->add_action( 'admin_menu', $polly_configuration, 'display_options' );

		$this->loader->add_action( 'admin_menu', $translate_configuration, 'amazon_ai_add_menu' );
		$this->loader->add_action( 'admin_menu', $translate_configuration, 'display_options' );

		$this->loader->add_action( 'admin_menu', $podcast_configuration, 'amazon_ai_add_menu' );
		$this->loader->add_action( 'admin_menu', $podcast_configuration, 'display_options' );

		$this->loader->add_action( 'admin_menu', $alexa_configuration, 'amazon_ai_add_menu' );

        $this->loader->add_action( 'admin_menu', $cloudfront_configuration, 'amazon_ai_add_menu');

        $plugin = plugin_basename( plugin_dir_path( dirname( __FILE__)) . 'amazonpolly.php');

        $this->loader->add_filter( 'wp_kses_allowed_html', $this->common, 'allowed_tags_kses');
        $this->loader->add_filter( 'tiny_mce_before_init', $this->common, 'allowed_tags_tinymce');
				$this->loader->add_filter( "pre_update_option_amazon_polly_secret_key_fake", $this->common, 'aws_configuration_update', 10, 2 );



        $this->loader->add_action('wp_ajax_display_stack_details_creation', $cloudfront_configuration, 'display_stack_details_creation');
        $this->loader->add_action('wp_ajax_begin_cloudformation', $cloudformation_service, 'begin_cloudformation');
        $this->loader->add_action('wp_ajax_update_cloudformation', $cloudformation_service, 'update_cloudformation');
        $this->loader->add_action('wp_ajax_check_acm_cert_creation', $cloudformation_service, 'check_acm_cert_creation');
        $this->loader->add_action('wp_ajax_check_cf_creation', $cloudformation_service, 'check_cf_creation');
        $this->loader->add_action('wp_ajax_create_cf_invalidation', $cloudformation_service, 'create_cf_invalidation');
        $this->loader->add_action('wp_ajax_get_cf_invalidation', $cloudformation_service, 'get_cf_invalidation');
        $this->loader->add_action('wp_ajax_update_installation_settings', $cloudformation_service, 'update_installation_settings');
        $this->loader->add_action('wp_ajax_delete_cloudformation', $cloudformation_service, 'delete_cloudformation');
        $this->loader->add_action('wp_ajax_get_stack_creation_status', $cloudformation_service, 'get_stack_creation_status');
        $this->loader->add_action('wp_ajax_check_stack_deletion', $cloudformation_service, 'check_stack_deletion');
        $this->loader->add_action('wp_ajax_get_dv_dns_info', $cloudformation_service, 'get_dv_dns_info');
        $this->loader->add_action('wp_ajax_get_cdn_alias_dns_info', $cloudformation_service, 'get_cdn_alias_dns_info');
        $this->loader->add_action('wp_ajax_validate_cdn_alias_mapping', $cloudformation_service, 'validate_cdn_alias_mapping');
        $this->loader->add_action('wp_ajax_get_stack_state', $cloudformation_service, 'get_stack_state');
        $this->loader->add_action('wp_ajax_complete_setup', $cloudformation_service, 'complete_setup');


    }


	private function update_aws_configuration() {
		error_log("SAVING");
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// Front-end
		$plugin_public = new Amazonpolly_Public( $this->get_plugin_name(), $this->get_version(), $this->common);

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_filter( 'the_content', $plugin_public, 'content_filter', 99999 );
		$this->loader->add_filter( 'the_title', $plugin_public, 'title_filter', 99999, 2 );
		$this->loader->add_action( 'customize_register', $plugin_public, 'customize_register' );

		// Podcast
		$amazon_pollycast = new Amazonpolly_PollyCast($this->common);
		$this->loader->add_filter( 'pre_get_posts', $amazon_pollycast, 'filter_pre_get_posts' );
		$this->loader->add_filter( 'the_excerpt_rss', $amazon_pollycast, 'filter_force_html_decode', 99999 );

        if ( $this->common->is_podcast_enabled()) {
            $this->loader->add_action( 'init', $amazon_pollycast, 'create_podcast');
        }

        //Rewrite URLs if site acceleration is enabled and a CloudFront distribution has been deployed
        $url_rewriter = new AmazonAI_UrlRewriter();
        if ( $this->common->is_cloudfront_enabled() and $this->common->is_cloudfront_deployed()) {
            $this->loader->add_action( 'init', $url_rewriter, 'define_url_rewrite_hooks');
        }
    }


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Amazonpolly_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
