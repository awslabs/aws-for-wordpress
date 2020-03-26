<?php
/**
 * Class responsible for providing GUI for general configuration of the plugin
 *
 * @link       amazon.com
 * @since      2.5.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_GeneralConfiguration
{
	/**
	 * @var AmazonAI_Common
	 */
	private $common;

	/**
	 * AmazonAI_GeneralConfiguration constructor.
	 *
	 * @param AmazonAI_Common $common
	 */
	public function __construct(AmazonAI_Common $common) {
		$this->common = $common;
	}

    public function amazon_ai_add_menu()
    {
        $this->plugin_screen_hook_suffix = add_menu_page(__('AWS', 'amazon-ai'), __('AWS', 'amazon-ai'), 'manage_options', 'amazon_ai', array(
            $this,
            'amazonai_gui'
        ), '

		data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAABs1BMVEUAAAD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mQD/mAD/mQD/mQD/ng3/uU//mQD/mAD/mAD/nAj/xnH/x3P/mQD/mQH/qCb/sDj/mgP/ohf/piL/mQD/mAD/uEz/3an/tEP/oBL/v1//15v/v1//mQD/mQD/zH7/26X/zYH/1JT/z4X/vlv/z4X/mQD/mQD/wWT/rjX/z4b/0o7/0Yz/2qL/z4b/mQD/nQr/mgP/x3T/x3L/3ar/w2j/3ar/mQD/piH/ng3/yHX/wmb/1pf/zYL/mQD/x3P/rDH/xW7/0In/vlv/2aD/mQD/yXj/u1T/pBz/x3P/1pj/zYH/mQD/qiv/z4b/u1X/mQP/rDD/1ZX/mQD/mAD/nQz/pBr/nAf/rjb/w2n/mQD/mAD/mwX/vVv/3q3/mQH/pyP/mQD/mAD////QNrphAAAALnRSTlMAAAAmdBFp1P0HS776AjGh8gVx5T3vYWNjY1v+Jtc5svgMXM39GnziAS2a5Qcs8LydqQAAAMpJREFUGNNd0bFKA0EUheH/nwyYgIVBLIUoiEUELX0jn8k3sjTEFCK6CzaCCBYWgsqx2GXN2lyYjzNz4YwAoEqSdKdu1B6/SY9O1WPJU5LPgLirLvpkm+QjuqdHDm+mSd513xO3MA9504OlPfqTJNm8Vpz9Q9nGZPLVI49nf8lJcgcV5tPRovkLFWY7I5xBRcaIVFydj3AlleI4WYJyqFcDXifPiCzUi0vJzW2SlhSStillXUop61KaNulKxtP++n2G5gGXkk33G/wC8OuIazoN13oAAAAASUVORK5CYII=

		');
        $this->plugin_screen_hook_suffix = add_submenu_page('amazon_ai', 'General', 'General', 'manage_options', 'amazon_ai', array(
            $this,
            'amazonai_gui'
        ));


    }

    public function amazonai_gui()
    {
?>
				 <div class="wrap">
				 <div id="icon-options-general" class="icon32"></div>
				 <h1>AWS</h1>
				 <form method="post" action="options.php">
						 <?php

				settings_errors();
        settings_fields("amazon_ai");
        do_settings_sections("amazon_ai");
        submit_button();

?>
				 </form>

		 </div>
		 <?php
    }

    function display_options()
    {

        // ************************************************* *
        // ************** GENERAL SECTION ************** *
        add_settings_section('amazon_ai_general', "", array(
            $this,
            'general_gui'
        ), 'amazon_ai');
        add_settings_field('amazon_polly_access_key', __('AWS access key:', 'amazonpolly'), array(
            $this,
            'access_key_gui'
        ), 'amazon_ai', 'amazon_ai_general', array(
            'label_for' => 'amazon_polly_access_key'
        ));
        add_settings_field('amazon_polly_secret_key_fake', __('AWS secret key:', 'amazonpolly'), array(
            $this,
            'secret_key_gui'
        ), 'amazon_ai', 'amazon_ai_general', array(
            'label_for' => 'amazon_polly_secret_key_fake'
        ));


        register_setting('amazon_ai', 'amazon_polly_access_key');
        register_setting('amazon_ai', 'amazon_polly_secret_key_fake');

          add_settings_field('amazon_polly_region', __('AWS Region:', 'amazonpolly'), array(
              $this,
              'region_gui'
          ), 'amazon_ai', 'amazon_ai_general', array(
              'label_for' => 'amazon_polly_region'
          ));

          register_setting('amazon_ai', 'amazon_polly_region');


    }


    /**
     * Render the Access Key input for this plugin
     *
     * @since  1.0.0
     */
    function access_key_gui() {
        $access_key = get_option('amazon_polly_access_key');
        echo '<input type="text" class="regular-text" name="amazon_polly_access_key" id="amazon_polly_access_key" value="' . esc_attr($access_key) . '" autocomplete="off"> ';
        echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';
    }



    /**
     * Render the Secret Key input for this plugin
     *
     * @since  1.0.0
     */
    function secret_key_gui() {
        $secret_key = get_option('amazon_polly_secret_key_fake','********************');
        echo '<input type="password" class="regular-text" name="amazon_polly_secret_key_fake" id="amazon_polly_secret_key_fake" value="' . esc_attr($secret_key) . '" autocomplete="off"> ';
        echo '<p class="description" id="amazon_polly_access_key">Required only if you aren\'t using IAM roles</p>';
    }

    /**
     * Render the region input.
     *
     * @since  1.0.3
     */
    function region_gui() {

            $selected_region = $this->common->get_aws_region();

            $regions = array(
                'us-east-1' => 'US East (N. Virginia)',
                'us-east-2' => 'US East (Ohio)',
                'us-west-1' => 'US West (N. California)',
                'us-west-2' => 'US West (Oregon)',
                'eu-west-1' => 'EU (Ireland)',
                'eu-west-2' => 'EU (London)',
                'eu-west-3' => 'EU (Paris)',
                'eu-central-1' => 'EU (Frankfurt)',
                'ca-central-1' => 'Canada (Central)',
                'sa-east-1' => 'South America (Sao Paulo)',
                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                'ap-northeast-2' => 'Asia Pacific (Seoul)',
                'ap-south-1' => 'Asia Pacific (Mumbai)'
            );

            echo '<select name="amazon_polly_region" id="amazon_polly_region" >';
            foreach ($regions as $region_name => $region_label) {
                echo '<option label="' . esc_attr($region_label) . '" value="' . esc_attr($region_name) . '" ';
                if (strcmp($selected_region, $region_name) === 0) {
                    echo 'selected="selected"';
                }
                echo '>' . esc_attr__($region_label, 'amazon_polly') . '</option>';
            }
            echo '</select>';


    }



    function general_gui()
    {
        //Empty
    }

    function storage_gui()
    {
        //Empty
    }


}
