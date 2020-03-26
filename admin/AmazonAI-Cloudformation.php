<?php
/**
 * Class responsible for providing methods to communicate with AWS Cloudformation service and associated resources
 *
 * @link       amazon.com
 * @since      4.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_Cloudformation
{
    private $common;
    private $cloudformation_client;
    private $acm_client;
    private $cf_client;
    private $args;
    private $helper;
    private $stack_resources;

    public function __construct(AmazonAI_Common $common) {
        $this->common = $common;
        $this->cloudformation_client = $this->common->get_cloudformation_client();
        $this->acm_client = $this->common->get_acm_client();
        $this->cf_client = $this->common->get_cloudfront_client();
        $this->stack_resources = array();
        $this->helper = new Helper();
    }

    /**
     * Method defines the args array, which is later used for defining parameters for SDK calls
     *
     * @since           4.0.0
     */
    private function define_arguments() {
        //Remove http/https from address
        $origin = preg_replace('#^https?://#', '', get_option('amazon_cf_origin'));
        $cf_alias = preg_replace('#^https?://#', '', get_option('amazon_cf_alias'));

        //Remove non-alphanumeric characters
        $alphanum_siteurl = preg_replace("/[^a-zA-Z0-9]+/", "",$cf_alias);

        $this->args = array(
                'stack_name' => 'CFWPStack'.$alphanum_siteurl,
                'comment' => 'Created using the AWS for WordPress plugin',
                'stack_logical_token' => 'CFWPToken'.$alphanum_siteurl,
                'acm_logical_resourceid' => 'CFWPACM'.$alphanum_siteurl,
                'cf_logical_resourceid' => 'CFWPCloudFront'.$alphanum_siteurl,
                'acm_domain' => get_option('amazon_cf_alias'),
                'origin' => $origin,
                'origin_id' => 'CFWPOrigin'.$alphanum_siteurl,                
            );
    }

    /**
     * Method is called to verify is site owner has mapped alias to CloudFront
     *
     * @since           4.0.0
     */
    public function validate_cdn_alias_mapping() {
        try {
            $flag = false;
            $result = dns_get_record(get_option('amazon_cf_alias'));
            foreach ($result as $dns_record) {
                if ($dns_record["host"]==get_option('amazon_cf_alias') and $dns_record["target"]==get_option('amazon_cf_domain')) {
                    $flag = true;
                    wp_send_json_success(1);
                    break;
                }
            }
            if (!$flag) {
                $message = 'The CNAME or alias you entered ('.get_option('amazon_cf_alias').') is currently mapped to '.$result[0]["target"];
                throw new Exception($message);
            }
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");           
        }
        wp_die();
    }
    /**
     * Method is called when the Initiate Setup button is clicked
     * Calls methods to create template, and then create stack using this template.
     *
     * @since           4.0.0
     */
    public function begin_cloudformation() {
        try {
            $origin = preg_replace('#^https?://#', '', $_POST['cf_origin']);
            $alias = preg_replace('#^https?://#', '', $_POST['cf_alias']); 
            $origin = rtrim($origin,'/');
            $alias = rtrim($alias,'/');
            if (!($this->helper->validate_url($origin)) and !($this->helper->validate_url($alias))) {
                $message = 'CFWPInvalidData: The origin domain name and alternate domain name are not valid.';
                throw new Exception($message);
            }
            else if (!$this->helper->validate_url($origin)) {
                $message = 'CFWPInvalidData: The origin domain name is not valid.';
                throw new Exception($message);
            }
            else if (!$this->helper->validate_url($alias)) {
                $message = 'CFWPInvalidData: The alternate domain name is not valid.';
                throw new Exception($message);
            }

            update_option('amazon_cf_origin',$origin);
            update_option('amazon_cf_alias',$alias);                  
            $this->define_arguments();
            //Defines template for Create Stack call
            $this->define_template(true);

            $result = $this->create_stack($this->args['template']);
            update_option('amazon_stack_state','creating');
            update_option('amazon_cloudformation_stack_id',$result->get('StackId'));
            wp_send_json_success();
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }
    
    /**
     * Method is called when the stack is to be updated with the CloudFront distribution
     *
     * @since           4.0.0
     */    
    public function update_cloudformation() {
        try {
	        $this->define_arguments();
            $template = $this->define_template(true, true);
            $result = $this->update_stack($template);
            update_option('amazon_stack_state','updating');
            update_option('amazon_cloudformation_stack_id',$result->get('StackId'));
            update_option('amazon_stack_update_start_time',date("F j, Y, H:i:s e"));
            wp_send_json_success(get_option('amazon_stack_update_start_time'));
        } 
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");          
        }        
        wp_die();
    }
    /**
     * Method retrieves DNS mappings required for Domain Validation
     *
     * @since           4.0.0
     */      
    public function get_dv_dns_info() {
        try {
            $this->stack_resources = $this->get_stack_resources();
            $result = $this->get_acm_certificate();

            update_option('acm_dv_name', $result['DomainValidationOptions'][0]['ResourceRecord']['Name']);
            update_option('acm_dv_value', $result['DomainValidationOptions'][0]['ResourceRecord']['Value']);

            $result = array(
                'RequestedAt' => date("F j, Y, H:i:s e",strtotime($result['CreatedAt'])),
                'DVName' => rtrim($result['DomainValidationOptions'][0]['ResourceRecord']['Name'],'.'),
                'DVValue' => rtrim($result['DomainValidationOptions'][0]['ResourceRecord']['Value'],'.')
            );
            update_option('acm_cert_state','creating');
            wp_send_json_success(json_encode($result));
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
	   wp_die();
    }

   /**
     * Method retrieves DNS mappings for mapping the CNAME to CloudFront
     *
     * @since           4.0.0
     */
    public function get_cdn_alias_dns_info() {
        try {
            $result = array();
            $result['Name'] = get_option('amazon_cf_alias');
            $result['Value'] = get_option('amazon_cf_domain');
            wp_send_json_success($result);
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();        
    }

    /**
     * Method is called when the page loads, to check if a distribution has been created
     *
     * @since           4.0.0
     */
    public function get_stack_state() {
        try {
            wp_send_json_success(get_option('amazon_stack_state'));
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }    
        wp_die();
    }

    /**
     * Method is called to poll the status of the CloudFront distribution deployment
     *
     * @since           4.0.0
     */
    public function check_cf_creation() {
        try {
            $this->stack_resources = $this->get_stack_resources();

            $cf_status = '';
            //Returns the deployment status of the CloudFront distribution
            $result = $this->cf_client->getDistribution([
                                'Id' => get_option('amazon_cf_physical_resource_id'),
                            ]);
            $cf_status = $result['Distribution']['Status'];

            $result = $this->cloudformation_client->describeStackEvents([
                'StackName' => get_option('amazon_cloudformation_stack_id'),
            ]);
            $stack_events = $result['StackEvents'];
            $latest_stack_event = $result['StackEvents'][0];

            if ($latest_stack_event['ResourceStatus'] != 'UPDATE_IN_PROGRESS' and $latest_stack_event['ResourceStatus'] != 'CREATE_IN_PROGRESS' and $latest_stack_event['ResourceStatus'] != 'CREATE_COMPLETE' and $latest_stack_event['ResourceStatus'] != 'UPDATE_COMPLETE_CLEANUP_IN_PROGRESS' and $latest_stack_event['ResourceStatus'] != 'UPDATE_COMPLETE') {
                $message = 'CloudFormation stack is in an unexpected state. CloudFront distribution state is '.$cf_status.' and stack state is '.$latest_stack_event['ResourceStatus'];

                throw new Exception($message);
            }

            if($cf_status == "Deployed") {
                $cf_distribution = $this->get_cloudfront_distribution();
                $cf_domain = $cf_distribution['DomainName'];
                update_option('amazon_cf_domain',$cf_domain);
                update_option('cf_dist_state','created');
                update_option('amazon_stack_state','validating');
            }
            else if($cf_status == "InProgress") {
                update_option('cf_dist_state','creating');
            }
            $result = Array(
                'CFStatus' => $cf_status,
                'StackUpdateStartTime' => get_option('amazon_stack_update_start_time'),
                'CurrentTime' => strval(date("F j, Y, H:i:s e"))
            );
            wp_send_json_success($result);
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }

    /**
     * Method is called to poll the status of the Cloudformation stack deletion
     *
     * @since           4.0.0
     */
    public function check_stack_deletion() {
        try {
            //Checks the latest stack event
            $result = $this->cloudformation_client->describeStackEvents([
                'StackName' => get_option('amazon_cloudformation_stack_id'),
            ]);

            $stack_events = $result['StackEvents'];
            $latest_stack_event = $result['StackEvents'][0];

            if ($latest_stack_event['ResourceType'] == 'AWS::CloudFormation::Stack' and $latest_stack_event['ResourceStatus'] == 'DELETE_COMPLETE') {
                update_option('amazon_stack_state','null');
                wp_send_json_success('Deleted');
            }
            else if ($latest_stack_event['ResourceStatus'] == 'DELETE_COMPLETE' || $latest_stack_event['ResourceStatus'] == 'DELETE_IN_PROGRESS') {
                wp_send_json_success('The most recent stack event is '.$latest_stack_event['ResourceStatus'].' for '.$latest_stack_event['ResourceType']);
            }
            else {
                $message = "The stack is an unexpected state. The three latest stack events are:<br><br>".'
                1. <b>Resource Type</b>: '.$result['StackEvents'][0]['ResourceType'].' 
                 <b>Resource Status</b>: '.$result['StackEvents'][0]['ResourceStatus'].' 
                 <b>Status Reason</b>: '.$result['StackEvents'][0]['ResourceStatusReason'].'<br>
                2. <b>Resource Type</b>: '.$result['StackEvents'][1]['ResourceType'].' 
                 <b>Resource Status</b>: '.$result['StackEvents'][1]['ResourceStatus'].' 
                 <b>Status Reason</b>: '.$result['StackEvents'][1]['ResourceStatusReason'].'<br>
                3. <b>Resource Type</b>: '.$result['StackEvents'][2]['ResourceType'].' 
                 <b>Resource Status</b>: '.$result['StackEvents'][2]['ResourceStatus'].'
                 <b>Status Reason</b>: '.$result['StackEvents'][2]['ResourceStatusReason'].'<br>';
                 throw new Exception($message);
            }
        }
        catch (Exception $e) {
            $str_stack_does_not_exist = '['.get_option('amazon_cloudformation_stack_id').'] does not exist';
            if (strpos($e->getMessage(),$str_stack_does_not_exist)) {
                update_option('amazon_stack_state','null');
                wp_send_json_success('Deleted');
            }
            else {
                $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
                wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
            }
        }
        wp_die();
    }
    /**
     * Method is called to poll the status of the ACM Certificate
     *
     * @since           4.0.0
     */
    public function check_acm_cert_creation() {
        try {
            $cert = $this->get_acm_certificate();
            if ($cert['Status'] == 'ISSUED') {
                update_option('acm_cert_state', 'created');
            }
            $result = $this->cloudformation_client->describeStackEvents([
                'StackName' => get_option('amazon_cloudformation_stack_id'),
            ]);
            $stack_events = $result['StackEvents'];
            $latest_stack_event = $result['StackEvents'][0];
            if ($latest_stack_event['ResourceStatus'] != 'CREATE_IN_PROGRESS' and $latest_stack_event['ResourceStatus'] != 'CREATE_COMPLETE' and $latest_stack_event['ResourceStatus'] != 'CREATE_IN_PROGRESS' and $latest_stack_event['ResourceStatus'] != 'UPDATE_COMPLETE') {
                $message = 'Stack is in an unexpected state. Certificate state is: '.$cert['Status'].' and Stack state is: '.$stack_events[0]['ResourceStatus'];

                throw new Exception($message);
            }
            if ($latest_stack_event['ResourceStatus'] == 'CREATE_COMPLETE' || $latest_stack_event['ResourceStatus'] == 'UPDATE_COMPLETE') {
                update_option('amazon_stack_state','cert_created');
            }
            wp_send_json_success($latest_stack_event['ResourceStatus']);
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }

    /**
     * Method is called to update the 'amazon_cloudfront_enabled' option
     * The plugin checks for this option for URL rewriting
     *
     * @since           4.0.0
     */
    public function update_installation_settings() {

        try {
            if ( $_POST['rewrite'] == 'true') {
                if (get_option('amazon_cloudfront_enabled') != 'checked') {
                    update_option('amazon_cloudfront_enabled','checked');
                    wp_send_json_success("Updated");
                }
            }
            else {
                if ( get_option('amazon_cloudfront_enabled') == 'checked') {
                    update_option('amazon_cloudfront_enabled','');
                    wp_send_json_success("Updated");
                }
            }
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }

    /**
     * Method is called to update stack state and finish installation
     *
     * @since           4.0.0
     */
    public function complete_setup() {
        update_option('amazon_stack_state','ready');
        wp_die();
    }    

    /**
     * Method is called to add CloudFront invalidation
     *
     * @since           4.0.0
     */
    public function create_cf_invalidation() {
        try {
            $date = new DateTime();
            $caller_reference = strval($date->getTimestamp());
            update_option('cf_caller_reference',$caller_reference);
            $result = $cf_client->createInvalidation([
                'DistributionId' => get_option('amazon_cf_physical_resource_id'), 
                'InvalidationBatch' => [ 
                    'CallerReference' => $caller_reference,
                    'Paths' => [
                        'Items' => ['/*'],
                        'Quantity' => 1,
                    ],
                ],
            ]);
            wp_send_json_success($result['Invalidation']['Status']); //Completed is desired state
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }

    /**
     * Method is called to get CloudFront invalidation
     *
     * @since           4.0.0
     */
    public function get_cf_invalidation() {
        try {
            $result = $client->getInvalidation([
                'DistributionId' => get_option('amazon_cf_physical_resource_id'),
                'Id' => get_option('cf_caller_reference'),
            ]);
            wp_send_json_success($result['Invalidation']['Status']); //Completed is desired state
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }

    /**
     * Method is called to retrieve identifiers for the stack resources
     *
     * @since           4.0.0
     */
    public function get_stack_resources() {
        //This retrieves the resources which are there in the stack
        $result = $this->cloudformation_client->describeStackResources([
            'StackName' => get_option('amazon_cloudformation_stack_id'),
        ]);
        $resources = $result['StackResources'];     
        $stack_resources = array();
      
        foreach ($resources as $resource) {
            if( $resource['ResourceType'] == "AWS::CloudFront::Distribution") {
                $stack_resources['CloudFront'] = $resource;
                update_option('amazon_cf_physical_resource_id',$resource['PhysicalResourceId']);
            }
            else if ($resource['ResourceType'] == "AWS::CertificateManager::Certificate") {
                $stack_resources['ACM'] = $resource;
                update_option('acm_arn',$resource['PhysicalResourceId']);
            }
        }
        $this->stack_resources = $stack_resources;
        return $stack_resources;
    }

    /**
     * Method is called to retrieve details of CloudFront distribution
     *
     * @since           4.0.0
     */
    public function get_cloudfront_distribution() {
        $result = $this->cf_client->getDistribution([
            'Id' => get_option('amazon_cf_physical_resource_id'),
        ]);
        return $result['Distribution'];
    }

    /**
     * Method is called to retrieve details of ACM certificate
     *
     * @since           4.0.0
     */
    public function get_acm_certificate() {

        $result = $this->acm_client->describeCertificate([
                        'CertificateArn' => get_option('acm_arn'),
                    ]);
        return $result['Certificate'];
    }
    /**
     * Method is called to delete Cloudformation stack and associated resources.
     * Relevant Wordpress Options are also deleted
     *
     * @since           4.0.0
     */
    public function delete_cloudformation() {
        try {
            $result = $this->cloudformation_client->deleteStack([
                'StackName' => get_option('amazon_cloudformation_stack_id'),
            ]);

            delete_option('amazon_cf_origin');
            delete_option('amazon_cf_alias');
            delete_option('amazon_cf_domain');
            delete_option('amazon_cf_physical_resource_id');
            delete_option('amazon_cloudfront_enabled');
            delete_option('acm_dv_name');
            delete_option('acm_dv_value');
            delete_option('acm_cert_state');
            delete_option('cf_dist_state');
            delete_option('cf_caller_reference');
            delete_option('acm_arn');
            delete_option('amazon_stack_update_start_time');

            update_option('amazon_stack_state','deleting');
            wp_send_json_success();
        }
        catch (Exception $e) {
            $this->helper->log_error(get_option('aws_cloudfront_logfile'),'Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."\n".$e->getMessage());
            wp_send_json_error('Caught exception in method '.__METHOD__.' in class '.__CLASS__.': '."<br>".$e->getMessage()."\n");
        }
        wp_die();
    }

    /**
     * Called when uninstallation is triggered.
     * Method is called to delete Cloudformation stack and associated resources.
     * Relevant Wordpress Options are also deleted
     *
     * @since           4.0.0
     */
    public function delete_cloudformation_on_plugin_uninstall() {
        //Deleting options
        delete_option('amazon_cf_origin');
        delete_option('amazon_cf_alias');
        delete_option('amazon_cf_domain');
        delete_option('amazon_cf_physical_resource_id');
        delete_option('amazon_cloudfront_enabled');
        delete_option('acm_dv_name');
        delete_option('acm_dv_value');
        delete_option('acm_cert_state');
        delete_option('cf_dist_state');
        delete_option('cf_caller_reference');
        delete_option('acm_arn');
        delete_option('amazon_stack_update_start_time');

        //AWS SDK call to delete stack
        $result = $this->cloudformation_client->deleteStack([
            'StackName' => get_option('amazon_cloudformation_stack_id'),
        ]);
    }

    /**
     * Method which makes the SDK stack creation call.
     * Relevant Wordpress Options are also deleted
     *
     * @param           string $template                 Template for the stack.
     *
     * @since           4.0.0
     */
    public function create_stack($template) {
        $on_failure = 'ROLLBACK';
        $result = $this->cloudformation_client->createStack([
            'Capabilities' => [],
            'ClientRequestToken' => 'CreateStack'.$this->args['stack_logical_token'],
            'OnFailure' => $on_failure,
            'ResourceTypes' => ['AWS::CloudFront::Distribution','AWS::CertificateManager::Certificate'],
            'StackName' => $this->args['stack_name'],
            'TemplateBody' => $template,
            'Tags' => [
                          ['Key' => 'createdBy',
                           'Value' => 'AWSForWordPressPlugin',
                          ],
                      ],
        ]);
        return $result;
    }

    /**
     * Method which makes the SDK stack update call.
     * Relevant Wordpress Options are also deleted
     *
     * @param           string $template                 Template for the stack.
     *
     * @since           4.0.0
     */
    public function update_stack($template) {
        $result = $this->cloudformation_client->updateStack([
            'Capabilities' => [],
            'ClientRequestToken' => 'UpdateStack'.$this->args['stack_logical_token'],
            'ResourceTypes' => ['AWS::CloudFront::Distribution','AWS::CertificateManager::Certificate'],
            'StackName' => $this->args['stack_name'],
            'TemplateBody' => $template,
        ]);  
        return $result;
    }    

    /**
     * Defines the template for the stack creation.
     *
     * @since           4.0.0
     */
    public function define_template($acm = false, $cf = false) {
        $alias = get_option('amazon_cf_alias');
        $template_head = '
        {
            "Resources": {';
        $cf_resource = '
                "'.$this->args['cf_logical_resourceid'].'": {
                    "Type": "AWS::CloudFront::Distribution",
                    "Properties": {
                        "DistributionConfig": {
                            "Origins": [{
                                "DomainName": "'.$this->args['origin'].'",
                                "Id": "'.$this->args['origin_id'].'",
                                "CustomOriginConfig": {
                                        "HTTPPort": "80",
                                        "HTTPSPort": "443",
                                        "OriginProtocolPolicy": "match-viewer",
                                        "OriginSSLProtocols" : ["TLSv1", "TLSv1.1", "TLSv1.2"]
                                }
                            }],
                            "HttpVersion" : "http2",
                            "Enabled": "true",
                            "Comment": "'.$this->args['comment'].'",
                            "DefaultRootObject": "",
                            "Aliases": ["'.$alias.'"],
                            "CacheBehaviors": [{
                                    "AllowedMethods" : ["GET","HEAD"],
                                    "ForwardedValues" : {
                                        "QueryString": "true",
                                        "Cookies": {
                                            "Forward": "none"
                                        }
                                    },
                                    "PathPattern" : "wp-content/*",
                                    "TargetOriginId" : "'.$this->args['origin_id'].'",
                                    "ViewerProtocolPolicy": "redirect-to-https"
                                },
                                {
                                    "AllowedMethods" : ["GET","HEAD"],
                                    "ForwardedValues" : {
                                        "QueryString": "true",
                                        "Cookies": {
                                            "Forward": "none"
                                        }                                    
                                    },
                                    "PathPattern" : "wp-includes/*",
                                    "TargetOriginId" : "'.$this->args['origin_id'].'",
                                    "ViewerProtocolPolicy": "redirect-to-https"
                                },
                                {
                                    "AllowedMethods" : ["GET", "HEAD", "OPTIONS", "PUT", "PATCH", "POST", "DELETE"],
                                    "ForwardedValues" : {
                                        "QueryString": "true",
                                        "Cookies": {
                                            "Forward": "all"
                                        },
                                        "Headers": [
                                                "*"
                                            ]                                        
                                    },
                                    "PathPattern" : "wp-admin/*",
                                    "TargetOriginId" : "'.$this->args['origin_id'].'",
                                    "ViewerProtocolPolicy": "redirect-to-https"
                                },
                                {
                                    "AllowedMethods" : ["GET", "HEAD", "OPTIONS", "PUT", "PATCH", "POST", "DELETE"],
                                    "ForwardedValues" : {
                                        "QueryString": "true",
                                        "Cookies": {
                                            "Forward": "all"
                                            },
                                        "Headers": [
                                                "*"
                                            ]                                            
                                        },
                                    "PathPattern" : "wp-login.php",
                                    "TargetOriginId" : "'.$this->args['origin_id'].'",
                                    "ViewerProtocolPolicy": "redirect-to-https"
                                }],                            
                            "DefaultCacheBehavior": {
                                "AllowedMethods" : ["GET", "HEAD", "OPTIONS", "PUT", "PATCH", "POST", "DELETE"],
                                "SmoothStreaming": "false",
                                "ForwardedValues": {
                                    "QueryString": "true",
                                    "Cookies": {
                                        "Forward": "whitelist",
                                        "WhitelistedNames": ["comment_*","wordpress_*","wp-settings-*"]
                                    },
                                    "Headers": [
                                            "Host",
                                            "CloudFront-Forwarded-Proto",
                                            "CloudFront-Is-Desktop-Viewer",
                                            "CloudFront-Is-Mobile-Viewer",
                                            "CloudFront-Is-Tablet-Viewer"
                                        ]                                     
                                },
                                "TargetOriginId": "'.$this->args['origin_id'].'",
                                "ViewerProtocolPolicy": "redirect-to-https"
                            },
                            "PriceClass": "PriceClass_200",
                            "Restrictions" : {
                                "GeoRestriction" : {
                                    "RestrictionType" : "none"
                                }
                            },';
        if (get_option('acm_arn')) {
            $cf_resource = $cf_resource.'
                            "ViewerCertificate": {
                                "AcmCertificateArn": "'.get_option('acm_arn').'",
                                "MinimumProtocolVersion": "TLSv1",
                                "SslSupportMethod": "sni-only"
                            }';
        }
        else {
            $cf_resource = $cf_resource.'
                            "ViewerCertificate": {
                                "CloudFrontDefaultCertificate": "true"
                            }';
        }

        $cf_resource = $cf_resource.'
                        },
                        "Tags": [{
                            "Key": "createdBy",
                            "Value": "AWSForWordPressPlugin"
                            }]
                    }
                }';

        $acm_resource = '
                "'.$this->args['acm_logical_resourceid'].'" : {
                    "Type" : "AWS::CertificateManager::Certificate",
                    "Properties" : {
                        "DomainName" : "'.$this->args['acm_domain'].'",
                        "ValidationMethod" : "DNS",
                        "Tags": [{
                            "Key": "createdBy",
                            "Value": "AWSForWordPressPlugin"
                        }]
                    }
                }';

        $template_tail = '
            }
        }'; 
        $template = $template_head;
        if($acm and $cf) {
            $template = $template.$acm_resource.','.$cf_resource;
        }
        else if($acm) {
            $template = $template.$acm_resource;
        }
        $template = $template.$template_tail;
        $this->args['template'] = $template;
        return $template;
    }
}
