<?php
/**
 * Class responsible for providing GUI for CloudFront CDN configuration.
 *
 * @link       amazon.com
 * @since      4.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
**/

class AmazonAI_CloudFrontConfiguration
{
    private $common;
    private $cloudformation; //Required to retrieve Cloudformation service 

    public function __construct(AmazonAI_Common $common, AmazonAI_Cloudformation $cloudformation) {
        $this->common = $common;
        $this->cloudformation = $cloudformation;
    }

    public function amazon_ai_add_menu() {
        $this->plugin_screen_hook_suffix = add_submenu_page('amazon_ai', 'CloudFront', 'CloudFront', 'manage_options', 'amazon_cloudfront', array(
            $this,
            'amazon_cf_gui'
        ));
    }

    /**
     * This method adds all the necessary divs to the page (either hidden or displayed).
     * The divs' visibility is changed according to the state of the CloudFront deployment
     *
     * @since           4.0.0
     */
    public function amazon_cf_gui() {
?>
        <div class="wrap">
        <div id="icon-options-cloudfront" class="icon32"></div>
        <h1>Site Acceleration with Amazon CloudFront</h1>
<?php
        if ($this->common->validate_cloudfront_access()) {
            $this->display_stack_deletion_progress('none');
            //Menu if distribution has already been deployed
            if ( get_option('amazon_stack_state') == 'ready' ) {
                $this->display_stack_management_options('block');
                $this->display_delete_stack_options('block');
            }
            //Menu if a new distribution needs to be created
            else if (!get_option('amazon_stack_state') || get_option('amazon_stack_state') == 'null' || get_option('amazon_stack_state') == '') {
                $this->display_stack_creation_options();
                $this->display_stack_setup_progress('none');               
                $this->display_dv_dns_details('none');
                $this->display_cf_creation_info('none');
                $this->display_cdn_alias_dns_details('none');
                $this->display_stack_management_options('none');
                $this->display_delete_stack_options('none');
            }
            //Menu if setup is ongoing
            else {
                $this->display_stack_setup_progress('none');
                $this->display_dv_dns_details('none');
                $this->display_cf_creation_info('none');
                $this->display_cdn_alias_dns_details('none');
                $this->display_stack_management_options('none');
                $this->display_delete_stack_options('none');
            }
            //Section to display errors
            $this->display_setup_error('none');
        }
?>
        </div>
<?php
    }

    /**
     * This method adds the section for viewing the progress of the stack creation
     * and associated resources.
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_stack_setup_progress($display_style) {
        echo '
        <div id="setup_progress_div" class="progress-table-div" style="display:'.$display_style.';"><br>
            <table style="width: 80%;">
                <tr>
                    <td><h2 id="setup_heading">Setup in progress</h2></td>
                    <td align="right"><button id="restart_setup_btn_top"class="text-btn">Restart Setup<h2 style="display:inline-block;"><i class="fas fa-sync-alt" style="display:inline-block;padding-left:10px;"></i></h2></button></td>
                </tr>
            </table>
            <table class="progress-table">
                <tr id="tr_acm_cert">
                    <td class="label">Certificate Creation and Validation</td>
                    <td class="status">'.
                    $this->in_progress_icon('acm_cert_in_progress','none').
                    $this->success_icon('acm_cert_success','none').
                    $this->failure_icon('acm_cert_failure','none').
                    $this->pending_icon('acm_cert_pending','block').
                    $this->paused_icon('acm_cert_paused','none').
                    '</td>
                </tr>
                <tr id="tr_cf_dist">
                    <td class="label">CloudFront Distribution Deployment</td>
                    <td class="status">'.
                    $this->in_progress_icon('cf_dist_in_progress','none').
                    $this->success_icon('cf_dist_success','none').
                    $this->failure_icon('cf_dist_failure','none').
                    $this->pending_icon('cf_dist_pending','block').
                    $this->paused_icon('cf_dist_paused','none').
                    '</td>
                </tr>
                <tr id="tr_cdn_alias_dns">
                    <td class="label">Map Alias to CloudFront</td>
                    <td class="status">'.
                    $this->in_progress_icon('cdn_alias_dns_in_progress','none').
                    $this->success_icon('cdn_alias_dns_success','none').
                    $this->failure_icon('cdn_alias_dns_failure','none').
                    $this->pending_icon('cdn_alias_dns_pending','block').
                    $this->paused_icon('cdn_alias_dns_paused','none').
                    '</td>
                </tr>
            </table>
        </div>';
    }

    /**
     * This method adds the section for viewing the progress of the stack deletion
     * and associated resources.
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_stack_deletion_progress($display_style) {
        if (!isset($desc_text_stack_deletion_in_progress)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo '
        <div id="stack_deletion_progress_div" class="progress-table-div" style="display:'.$display_style.';"><br>
            <h2 id="stack_deletion_progress_heading">Deletion in progress</h2>
            <table class="progress-table">
                <tr id="tr_stack_deletion">
                    <td class="label">Deletion of CloudFront resources</td>
                    <td class="status">'.
                    $this->in_progress_icon('stack_deletion_in_progress','none').
                    $this->success_icon('stack_deletion_success','none').
                    $this->failure_icon('stack_deletion_failure','none').
                    '</td>
                </tr>
            </table>        
        <div class="text-content long">'.$desc_text_stack_deletion_in_progress.'</div>
        </div>';
    }

    /**
     * This method adds the section for form for initiating setup
     *
     * @since           4.0.0
     */
    public function display_stack_creation_options() {
?>
        <div id="create_stack_div">
        <h2>CloudFront Setup</h2>
        <div class="text-content long">
        <?php
        if (!isset($desc_text_amazon_cf)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo $desc_text_amazon_cf;
        ?>
        </div>
        <form method="post" action="options.php">
            <table class="form-table">
                <tr>
                    <td class="label">
                        <label for="amazon_cf_origin"><b>Origin Domain Name:</b></label>
                    </td>
                    <td class="input-textbox">
                        <input type="text" name="amazon_cf_origin" id="amazon_cf_origin" size = "40" value="<?php 
                        if (get_option('amazon_cf_origin')) {
                            echo esc_attr(get_option('amazon_cf_origin'));
                        }
                        else {
                            echo esc_attr(get_option('siteurl'));
                        }
                        ?>">
                    </td>
                    <td class="info-btn">
                        <a class="info" id="info_btn_amazon_cf_origin">?</a>
                    </td>
                </tr>
                <tr id="desc_row_amazon_cf_origin" style="display:none;">
                    <td colspan="2" class="desc-row">
                    <div class="text-content long">
                    <?php 
                    if (!isset($desc_text_amazon_cf_origin)) {
                        include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
                    }
                    echo $desc_text_amazon_cf_origin;
                    ?>                        
                    </div>
                    </td>                    
                </tr>
                <tr>
                    <td class="label" class="input-textbox">
                        <label for="amazon_cf_alias"><b>CloudFront Alternate Domain Name:</b></label>
                    </td>
                    <td>
                        <input type="text" name="amazon_cf_alias" id="amazon_cf_alias" size = "40" value="<?php echo esc_attr(get_option('amazon_cf_alias')) ?>">
                    </td>
                    <td class="info-btn">
                        <a class="info" id="info_btn_amazon_cf_alias">?</a>
                    </td>                    
                </tr>
                <tr id="desc_row_amazon_cf_alias" style="display:none;">
                    <td colspan="2" class="desc-row">
                    <div class="text-content long">
                    <?php 
                    if (!isset($desc_text_amazon_cf_alias)) {
                        include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
                    }
                    echo $desc_text_amazon_cf_alias;
                    ?>                        
                    </div>
                    </td>                   
                </tr>
                <tr>
                    <td></td>
                </tr>                               
            </table>        
        </form>

<?php
        if (!isset($desc_text_initiate_setup)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }        
        echo '
        <div class="text-content long">'.$desc_text_initiate_setup.'</div>';
        $this->amazon_cf_button_gui('create_stack_btn_div','stack_create_btn','Initiate Setup','button-primary','block')
?>
        </div>
<?php
    }

    /**
     * This method adds the section for viewing distribution info,
     * and options for enabling/disabling site acceleration
     * and stack deletion
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_stack_management_options($display_style) {

        echo '<div id="stack_management_div" style="display:'.$display_style.';"><br>';
        echo '<div id="stack_info_div">';

        if (get_option('amazon_stack_state') == 'ready') {
            if (!isset($desc_text_stack_ready)) {
                include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
            }
            echo '
            <h3>CloudFront Distribution</h3>';
            $cf_distribution = $this->cloudformation->get_cloudfront_distribution();
            echo '
            <div class="text-content long">
                CloudFront created distribution <b>'.get_option('amazon_cf_origin').'</b> at <b>'.$cf_distribution['DomainName'].'</b> with an alternate domain name of <b>'.get_option('amazon_cf_alias').'</b>
            </div>';
            echo '
            <div class="text-content long">'.$desc_text_stack_ready.'
            </div>
            <table class="form-table">
                <tr>
                <th scope="row">
                    <label for="amazon_cloudfront_enabled">Activate Site Acceleration:</label>
                </th>
                <td>
                    <input type="checkbox" name="amazon_cloudfront_enabled" id="amazon_cloudfront_enabled" '.get_option('amazon_cloudfront_enabled').'>
                </td>
                </tr>
            </table>
            ';

        }
        echo '
        </div>
        <table >
            <tr>
            <td>';
        $this->amazon_cf_button_gui('update_installation_div','update_installation_btn','Save Changes','button-primary','block');
        echo '
            </td>
            </tr>
        </table>
        ';
        echo '</div>';

    }

    /**
     * This method adds the section for viewing distribution info,
     * and options for enabling/disabling site acceleration
     * immediately after stack creation is complete.
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_stack_details_creation() {
        if (!isset($desc_text_stack_ready)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo '
        <h3>CloudFront Distribution</h3>';
        $cf_distribution = $this->cloudformation->get_cloudfront_distribution();
        echo '
        <div class="text-content long">
            CloudFront created distribution <b>'.get_option('amazon_cf_origin').'</b> at <b>'.$cf_distribution['DomainName'].'</b> with an alternate domain name of <b>'.get_option('amazon_cf_alias').'</b>
        </div>';
        echo '
        <div class="text-content long">'.$desc_text_stack_ready.'
        </div>
        <table class="form-table">
            <tr>
            <th scope="row">
                <label for="amazon_cloudfront_enabled">Activate Site Acceleration:</label>
            </th>
            <td>
                <input type="checkbox" name="amazon_cloudfront_enabled" id="amazon_cloudfront_enabled" '.get_option('amazon_cloudfront_enabled').'>
            </td>
            </tr>
        </table>
        ';
        wp_die();
    }

    /**
     * This method adds the text to be displayed during deployment of CloudFront distribution
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_cf_creation_info($display_style) {
        if (!isset($desc_text_cf_in_progress)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo '
        <div id="desc_text_cf_in_progress_div" style="display:'.$display_style.';"><br>
            <div class="text-content long">'.$desc_text_cf_in_progress.'
            </div>
            <div>This page refreshes automatically while the deployment is in progress. Last updated at <span id="current_time"></span>.</div>
        </div>';
    }
    /**
     * This method adds the section for deleting distribution
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_delete_stack_options($display_style) {
        if (!isset($desc_text_remove_site_acceleration)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo '
        <div id="delete_stack_options_div" class="progress-table-div" style="display:'.$display_style.';"><br>
        <h2 id="delete_stack_heading">Remove Site Acceleration</h2>
        <div class="text-content long">'.$desc_text_remove_site_acceleration.'
        </div>';
        $this->amazon_cf_button_gui('delete_stack_div','stack_delete_btn','Remove Site Acceleration','delete','block');
        echo '
        </div>';

    }

    /**
     * This method adds the section for displaying DNS records for Certificate Domain Validation
     *
     * @param           string $display_style                 Default CSS Display Style for div
     * @since           4.0.0
     */
    public function display_dv_dns_details($display_style) {
        if (!isset($desc_text_validate_acm_cert)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo '
        <div id="dv_dns_details_div" style="display:'.$display_style.';"><br>
            <div class="text-content long">'.$desc_text_validate_acm_cert.'
            </div>
            <div class="text-content long">The certificate was requested at <span id="acm_cert_request_info"></span> and must be validated within 72 hours.</div>
            <h4>To validate the certificate, add the following record in your DNS records:</h4>
            <table id="dv_dns_mappings_table_wrap">
                <tr>
                    <td>
                        <table class="info-table" id="dv_dns_mappings_table">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Value</th>
                            </tr>
                            <tr>
                                <td id="dv_dns_name">DefaultLongName</td>
                                <td>CNAME</td>
                                <td id="dv_dns_value">DefaultLongValue</td>
                            </tr>                            
                        </table>
                    </td>
                </tr>          
            </table>';
        $this->amazon_cf_button_gui('validate_acm_div','acm_validate_btn','Check status of SSL/TLS certificate','button-primary','block');
        echo '
        </div>
        ';
    }    

    /**
     * This method renders the html for mapping the CNAME to the distribution
     *
     * @since           4.0.0
     */
    public function display_cdn_alias_dns_details($display_style) {
        if (!isset($desc_text_cdn_alias_dns)) {
            include plugin_dir_path( dirname( __FILE__)) . 'includes/class-amazonpolly-TextDescriptions.php';
        }
        echo '
        <div id="cdn_alias_dns_details_div" style="display:'.$display_style.';"><br>
            <div class="text-content long">'.$desc_text_cdn_alias_dns.'
            </div>

            <h4>Add the following record in your DNS records:</h4>
            <table id="alias_dns_mappings_table_wrap">
                <tr>
                    <td>
                        <table class="info-table" id="cdn_alias_dns_mappings_table">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>                                
                                <th>Value</th>
                            </tr>
                            <tr>
                                <td id="cdn_alias_dns_name">DefaultLongName</td>
                                <td>CNAME</td>
                                <td id="cdn_alias_dns_value">DefaultLongValue</td>
                            </tr>                            
                        </table>
                    </td>
                </tr>          
            </table>';
        $this->amazon_cf_button_gui('cdn_alias_dns_map_div','cdn_alias_dns_map_btn','Check status of CloudFront DNS record','button-primary','block'); 

        echo '
        </div>
        ';        
    }

    /**
     * This method renders the html for error messages
     *
     * @since           4.0.0
     */
    public function display_setup_error($display_style) {
        echo '
        <div id="setup_error_div" style="display:'.$display_style.';">
            <h3>Error in Setup</h3>
            <div class="text-content long"><span id="setup_error_text"></span>
            </div>';            
        //Button to restart setup
        echo '
            <table id="setup_error_btns" style="display:none;">
                <tr>
                <td>';
        $this->amazon_cf_button_gui('retry_setup_initiate_div','retry_setup_initiate_btn','Retry','','none',false); 
        $this->amazon_cf_button_gui('retry_dv_div','retry_dv_btn','Retry','','none',false);       
        $this->amazon_cf_button_gui('retry_acm_div','retry_acm_btn','Retry','','none',false);
        $this->amazon_cf_button_gui('retry_update_stack_div','retry_update_stack_btn','Retry','','none',false);
        $this->amazon_cf_button_gui('retry_cf_div','retry_cf_btn','Retry','','none',false);
        $this->amazon_cf_button_gui('retry_cdn_alias_dns_div','retry_cdn_alias_dns_btn','Retry','','none',false);
        $this->amazon_cf_button_gui('retry_delete_stack_div','retry_delete_stack_btn','Retry','','none',false);
        $this->amazon_cf_button_gui('retry_check_stack_deletion_div','retry_check_stack_deletion_btn','Retry','','none',false);        
        echo '
            </td>
            <td>';
        $this->amazon_cf_button_gui('restart_setup_div','restart_setup_btn','Restart Setup','delete','inline-block',false);
        echo '
                    </td>
                    <td>';
        $this->amazon_cf_button_gui('override_dns_check','override_dns_check','Override DNS Check','','inline-block',false);
        echo '
                    </td>
                    </tr>
                </table>
            <span id="setup_error_response_wrapper">
            <h4>The following response was returned:</h4>
            <span id="setup_error_response">Error response</span>
            </span>
        </div>';
    }
    /**
     * This method renders the html for a text box
     *
     * @since           4.0.0
     */
    public function amazon_cf_text_field_gui($args) {
        echo '<input type="text" name="'.$args['name'].'" id="'.$args['name'].'" size = "'.$args['size'].'" value="'.$args['value'].'">';
    }

    /**
     * This method renders the html for a checkbox
     *
     * @since           4.0.0
     */
    public function amazon_cf_checkbox_gui($args) {
           echo '<input type="checkbox" name="'.$args['name'].'" id="'.$args['name'].'" ' .$args['value']. '> ';

    }

    /**
     * This method renders the html for a button
     *
     * @since           4.0.0
     */
    public function amazon_cf_button_gui($div_id, $btn_id, $btn_text,$btn_display, $display_style, $para = true) {
        echo '<div id="'.$div_id.'" style="display:'.$display_style.';">';
        if ($para) {
            echo '<p>';
        }
        echo '<input type="submit" name="'.$btn_id.'" id="'.$btn_id.'" class="button '.$btn_display.'" value="'.$btn_text.'"/>';
        if ($para) {
            echo '</p>';
        }
        echo '</div>';

    }

    public function pending_icon($id,$display) {
        $html = '
        <span id="'.$id.'" style="display:'.$display.';">
            <i class="fas fa-clock fa-2x"></i>
        </span>';
        return $html;         
    }

    public function failure_icon($id,$display) {
        $html = '
        <span id="'.$id.'" style="display:'.$display.';">
            <i class="fas fa-times-circle fa-2x"></i>
        </span>';
        return $html;         
    }

    public function paused_icon($id,$display) {
        $html = '
        <span id="'.$id.'" style="display:'.$display.';">
            <i class="fas fa-pause-circle fa-2x"></i>
        </span>';
        return $html;         
    }

    public function success_icon($id,$display) {
        $html = '
        <span id="'.$id.'" style="display:'.$display.';" class="checkmark">
            <i class="fas fa-check-circle fa-2x"></i>
        </span>';
        return $html;       
    }

    /**
     * This method renders the html for a loading animation
     *
     * @since           4.0.0
     */
    public function in_progress_icon($id,$display) {

        $html = '
        <span id="'.$id.'" style="display:'.$display.';">
            <i class="fas fa-spinner fa-spin fa-fw fa-2x"></i>
        </span>';
        return $html;

    }
}
