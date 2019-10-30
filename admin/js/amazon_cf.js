/* This is the time in milliseconds for which the polling functions
 * wait before sending another request
 */
const WAIT_TIME_IN_MS = 10000;
const ACM_CHECK_LIMIT = 20;
var cl_count = 0;
var cf_check_count = 0;
var acm_check_count = 0;

var stack_responses = {
    CREATED: 'CREATE_COMPLETE',
    UPDATED: 'UPDATE_COMPLETE',
    CREATING: 'CREATE_IN_PROGRESS',
    CF_CREATED: 'Deployed',
    DELETED: 'Deleted',
    INVALID: 'CFWPInvalidData'
};

var stack_states = {
    CREATING: 'creating',
    CERT_CREATED: 'cert_created',
    UPDATING: 'updating',
    VALIDATING: 'validating',
    DELETING: 'deleting'
};

jQuery(document).ready(function($) {
    $('#info_btn_amazon_cf_origin').click(function() {
        if ($('#desc_row_amazon_cf_origin').is(":visible")) {
            $('#desc_row_amazon_cf_origin').hide();
        } else {
            $('#desc_row_amazon_cf_origin').show();
        }
    });

    $('#info_btn_amazon_cf_alias').click(function() {
        if ($('#desc_row_amazon_cf_alias').is(":visible")) {
            $('#desc_row_amazon_cf_alias').hide();
        } else {
            $('#desc_row_amazon_cf_alias').show();
        }
    });
    /**
     * Method is triggered when Create Distribution button is clicked
     * and calls the begin_cloudformation method
     */
    $('#create_stack_btn_div').on('click', '#stack_create_btn', function(e) {
        e.preventDefault();
        begin_cloudformation();
    });

    function display_setup_state(setup_process_step, status) {
        $('#setup_error_btns').show();
        $('#' + setup_process_step + '_in_progress').hide();
        $('#' + setup_process_step + '_success').hide();
        $('#' + setup_process_step + '_pending').hide();
        $('#' + setup_process_step + '_failure').hide();
        $('#' + setup_process_step + '_paused').hide();

        $('#' + setup_process_step + '_' + status).show();
    }

    function disable_retry_buttons() {
        $('#retry_setup_initiate_btn').prop('disabled', true);
        $('#retry_dv_btn').prop('disabled', true);
        $('#retry_acm_btn').prop('disabled', true);
        $('#retry_update_stack_btn').prop('disabled', true);
        $('#retry_cf_btn').prop('disabled', true);
        $('#retry_cdn_alias_dns_btn').prop('disabled', true);
        $('#retry_delete_stack_btn').prop('disabled', true);
        $('#override_dns_check').prop('disabled',true);

        $('#restart_setup_btn').prop('disabled', true);
    }

    function display_retry_button(retry_step) {
        $('#setup_error_btns').show();
        $('#retry_setup_initiate_div').hide();
        $('#retry_dv_div').hide();
        $('#retry_acm_div').hide();
        $('#override_dns_check').hide();
        $('#retry_cdn_alias_dns_div').hide();
        $('#retry_update_stack_div').hide();
        $('#retry_cf_div').hide();
        $('#retry_delete_stack_div').hide();

        $('#retry_' + retry_step + '_btn').prop('disabled', false);
        $('#retry_' + retry_step + '_div').show("fast");
        $('#restart_setup_btn').prop('disabled', false);
        $('#restart_setup_div').show("fast");
    }

    function display_setup_error(error_msg, php_error_response, setup_step, retry_button) {
        $('#setup_error_text').html(error_msg);
        $('#setup_error_response').html(php_error_response);
        display_setup_state(setup_step, 'failure');
        display_retry_button(retry_button);
        $('#setup_error_response_wrapper').show();
        $('#setup_error_div').show();
    }

    function display_override_button(){
        $('#override_dns_check').prop('disabled',false);
        $('#override_dns_check').show("fast");
    }

    function override_dns_check(){
        $('#setup_error_div').hide();
        disable_retry_buttons();
        display_setup_state('cdn_alias_dns', 'success');
        $('#cdn_alias_dns_details_div').hide();
        $('#cdn_alias_dns_map_btn').prop('disabled', false);
        display_installation_info();
    }

    /**
     * Method calls begin_cloudformation which initiates stack creation
     */
    function begin_cloudformation() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'begin_cloudformation',
                cf_origin: $('#amazon_cf_origin').val(),
                cf_alias: $('#amazon_cf_alias').val()
            },
            beforeSend: function(response) {
                $('#setup_error_response_wrapper').hide();
                disable_retry_buttons();
                display_setup_state('acm_cert', 'in_progress');
            },
            success: function(response) {
                var message = "There was an error initiating the setup process. Try again.";
                if (response['success']) {
                    $("#create_stack_div").hide();
                    $("#setup_progress_div").show();
                    $('#setup_error_div').hide();
                    setTimeout(function() {
                        display_dv_dns_mapping();
                    }, WAIT_TIME_IN_MS);
                } else if (!response['success'] && response['data'].includes(stack_responses.INVALID)) {
                    $('#restart_setup_div').hide();
                    display_setup_error(message, response['data'], 'acm_cert', 'setup_initiate');
                } else {
                    $("#create_stack_div").hide();
                    $("#setup_progress_div").show();
                    display_setup_error(message, response['data'], 'acm_cert', 'setup_initiate');
                }
            }
        });
    }

    /**
     * Handle errors in setup initiation
     */
    $('#retry_setup_initiate_div').on('click', '#retry_setup_initiate_btn', function(e) {
        e.preventDefault();
        begin_cloudformation();
    });

    /**
     * Method displays the DNS mapping to be added for Domain Validation
     */
    function display_dv_dns_mapping() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'get_dv_dns_info',
            },
            beforeSend: function() {
                $('#setup_error_response_wrapper').hide();
                disable_retry_buttons();
                display_setup_state('acm_cert', 'in_progress');

                $('#setup_progress_div').show();
            },
            success: function(response) {
                if (response['success']) {
                    $('#setup_error_div').hide();
                    display_setup_state('acm_cert', 'paused');
                    var result = JSON.parse(response['data']);
                    $('#acm_cert_request_info').html(result['RequestedAt']);
                    $('#dv_dns_name').html(result['DVName']);
                    $('#dv_dns_value').html(result['DVValue']);
                    $('#dv_dns_details_div').show();
                } else {
                    var message = "There was an error retrieving DNS records for validating domain ownership. Try again.";
                    display_setup_error(message, response['data'], 'acm_cert', 'dv');
                }
            }
        });
    }

    /**
     * Handle errors during certificate creation and validation
     */
    $('#retry_dv_div').on('click', '#retry_dv_btn', function(e) {
        e.preventDefault();
        display_dv_dns_mapping();
    });

    /**
     * Method is triggered to check for poll ACM Cert creation
     */
    function check_acm_cert_creation() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'check_acm_cert_creation',
            },
            beforeSend: function() {
                disable_retry_buttons();
                $('#acm_validate_btn').prop('disabled', true)
                $('#setup_error_response_wrapper').hide();
                display_setup_state('acm_cert', 'in_progress');
            },
            success: function(response) {
                if (response['success']) {
                    if (response['data'] == stack_responses.CREATED || response['data'] == stack_responses.UPDATED) {
                        $('#setup_error_div').hide();
                        $('#dv_dns_details_div').hide();
                        display_setup_state('acm_cert', 'success');
                        update_cloudformation_stack();
                    } else if (response['data'] == stack_responses.CREATING) {
                        if (acm_check_count >= ACM_CHECK_LIMIT) {
                            acm_check_count = 0;
                            var message = "It is taking longer than expected to validate your ACM certificate. Please verify that the DNS record has been added, and then try again.";
                            display_setup_error(message, '', 'acm_cert', 'acm');
                            $('#setup_error_response_wrapper').hide();
                            display_setup_state('cf_dist', 'pending');
                            display_setup_state('cdn_alias_dns', 'pending');
                        } else {
                            setTimeout(function() {
                                check_acm_cert_creation();
                            }, WAIT_TIME_IN_MS);
                            acm_check_count++;
                        }
                    }
                } else {
                    acm_check_count = 0;
                    var message = "There was an error validating the ACM certificate. Try again.";
                    display_setup_error(message, response['data'], 'acm_cert', 'acm');
                    display_setup_state('cf_dist', 'pending');
                    display_setup_state('cdn_alias_dns', 'pending');
                }
            }
        });
    }

    /**
     * Method is triggered when user confirms DNS mappings have been added
     * and polls for status of the ACM certificate
     */
    $('#validate_acm_div').on('click', '#acm_validate_btn', function(e) {
        e.preventDefault();
        check_acm_cert_creation();
    });

    /**
     * Handle errors during certificate creation and validation
     */
    $('#retry_acm_div').on('click', '#retry_acm_btn', function(e) {
        e.preventDefault();
        check_acm_cert_creation();
    });

    /**
     * Method is triggered to call function to add CloudFront resource
     */
    function update_cloudformation_stack() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'update_cloudformation',
            },
            beforeSend: function(response) {
                $('#dv_dns_details_div').hide();
                disable_retry_buttons();
                $('#setup_error_response_wrapper').hide();
                display_setup_state('acm_cert', 'success');
                display_setup_state('cf_dist', 'in_progress');
            },
            success: function(response) {
                if (response['success']) {
                    $('#stack_update_start_time').html(response['data']);
                    $('#current_time').html(response['data']);
                    $('#desc_text_cf_in_progress_div').show();
                    $('#setup_error_div').hide();
                    display_setup_state('cf_dist', 'in_progress');

                    setTimeout(function() {
                        check_cf_creation();
                    }, WAIT_TIME_IN_MS);
                } else {
                    var message = "There was an error creating the CloudFront distribution. Try again.";
                    display_setup_error(message, response['data'], 'cf_dist', 'update_stack');
                    display_setup_state('cdn_alias_dns', 'pending');
                }
            }
        });
    }

    /**
     * Handle errors during update stack call
     */
    $('#retry_update_stack_div').on('click', '#retry_update_stack_btn', function(e) {
        e.preventDefault();
        update_cloudformation_stack();
    });

    /**
     * Method to poll the status of the CloudFront distribution during creation
     */
    function check_cf_creation() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'check_cf_creation',
            },
            beforeSend: function(response) {
                disable_retry_buttons();
                $('#setup_error_response_wrapper').hide();
                display_setup_state('cf_dist', 'in_progress');
            },
            success: function(response) {
                if (response['success']) {
                    var result = response['data'];
                    if (result['CFStatus'] == stack_responses.CF_CREATED) {
                        $('#desc_text_cf_in_progress_div').hide();
                        $('#setup_error_response_wrapper').hide();
                        $('#setup_error_div').hide();
                        display_setup_state('cf_dist', 'success');
                        display_cdn_alias_dns_mapping();
                    } else {
                        $('#stack_update_start_time').html(result['StackUpdateStartTime']);
                        $('#current_time').html(result['CurrentTime']);
                        $('#desc_text_cf_in_progress_div').show();
                        $('#setup_error_response_wrapper').hide();
                        $('#setup_error_div').hide();
                        setTimeout(function() {
                            check_cf_creation();
                        }, WAIT_TIME_IN_MS);
                    }
                } else {
                    var message = "There was an error retrieving the status of the CloudFront distribution. Try again.";
                    display_setup_error(message, response['data'], 'cf_dist', 'cf');
                    display_setup_state('cdn_alias_dns', 'pending');
                }
            }
        });
    }

    /**
     * Handle errors during creation of CloudFront resource
     */
    $('#retry_cf_div').on('click', '#retry_cf_btn', function(e) {
        e.preventDefault();
        check_cf_creation();
    });

    /**
     * Method displays the DNS mapping to be added for 
     * mapping the alias/CNAME to the CloudFront distribution
     */
    function display_cdn_alias_dns_mapping() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'get_cdn_alias_dns_info',
            },
            beforeSend: function() {
                disable_retry_buttons();
                $('#setup_error_response_wrapper').hide();
            },
            success: function(response) {
                if (response['success']) {
                    var result = response['data'];
                    $('#cdn_alias_dns_name').html(result['Name']);
                    $('#cdn_alias_dns_value').html(result['Value']);
                    $('#cdn_alias_dns_details_div').show();
                } else {
                    var message = "There was an error retrieving the DNS record for mapping the alternate domain name to your CloudFront distribution. Try again.";
                    display_setup_error(message, response['data'], 'cdn_alias_dns', 'cdn_alias_dns');
                }
            }
        });
    }

    /**
     * Method is triggered when user confirms DNS mappings have been added
     */
    $('#cdn_alias_dns_map_div').on('click', '#cdn_alias_dns_map_btn', function(e) {
        e.preventDefault();
        validate_cdn_alias_mapping();
    });

    /**
     * Method checks if alias has been mapped to the CLoudFront distribution
     */
    function validate_cdn_alias_mapping() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'validate_cdn_alias_mapping',
            },
            beforeSend: function() {
                disable_retry_buttons();
                $('#setup_error_response_wrapper').hide();
                display_setup_state('cdn_alias_dns', 'in_progress');
                $('#cdn_alias_dns_map_btn').prop('disabled', true);
            },
            success: function(response) {
                var message = "There was an error validating the DNS record for mapping the CNAME to CloudFront. Try again.";
                if (response['success']) {
                    $('#setup_error_div').hide();
                    display_setup_state('cdn_alias_dns', 'success');
                    $('#cdn_alias_dns_details_div').hide();
                    $('#cdn_alias_dns_map_btn').prop('disabled', false);
                    display_installation_info();
                } else {
                    display_setup_error(message, response['data'], 'cdn_alias_dns', 'cdn_alias_dns');
                    display_override_button();
                }
            }
        });
    }
    /**
     * Handle errors during creation of CloudFront resource
     */
    $('#retry_cdn_alias_dns_div').on('click', '#retry_cdn_alias_dns_btn', function(e) {
        e.preventDefault();
        display_cdn_alias_dns_mapping();
        validate_cdn_alias_mapping();
    });

    /**
     * Method is triggered when distribution is deployed.
     * The method displays the information for the distribution.
     */
    function display_installation_info() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'display_stack_details_creation',
            },
            success: function(response) {
                $('#update_installation_div').show();
                $('#delete_stack_div').show();

                $('#stack_info_div').html(response);
                $('#stack_management_div').show();
                $('#delete_stack_options_div').show();

                complete_setup();
            }
        });
    }

    /**
     * Method is called to update stack state 
     * and finish installation.
     */
    function complete_setup() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'complete_setup',
            },
            success: function(response) {
                $('#setup_heading').html("Setup completed");
            }
        });
    }

    /**
     * Method to enable/disable site acceleration
     */
    $('#update_installation_div').on('click', '#update_installation_btn', function(e) {
        e.preventDefault();
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'update_installation_settings',
                rewrite: $('#amazon_cloudfront_enabled').prop('checked')
            },
            success: function(response) {
                if (response['data'] == 'Updated') {
                    alert("Options updated");
                }
            }
        });
    });

    /**
     * Method to enable/disable site acceleration
     */
    $('#restart_setup_div').on('click', '#restart_setup_btn', function(e) {
        e.preventDefault();
        var confirmation_text = "Are you sure you want to restart?\nRestarting will delete all created resources.";
        if (confirm(confirmation_text)) {
            delete_cloudformation(confirmation_text);
        }
    });

    $('#override_dns_check').on('click', '#override_dns_check', function(e){
        e.preventDefault();
        var confirmation_text = "Are you sure you want to override this DNS check?\nMake sure your DNS records are correct before proceeding.";
        if (confirm(confirmation_text)) {
            override_dns_check(confirmation_text);
        }
    });

    $('#restart_setup_btn_top').click(function(e) {
        e.preventDefault();
        var confirmation_text = "Are you sure you want to restart?\nRestarting will delete all created resources.";
        if (confirm(confirmation_text)) {
            delete_cloudformation(confirmation_text);
        }
    });

    /**
     * Calls method for deleting stack
     */
    $('#delete_stack_div').on('click', '#stack_delete_btn', function(e) {
        e.preventDefault();
        var confirmation_text = "Are you sure you want to remove site acceleration?";
        if (confirm(confirmation_text)) {
            delete_cloudformation();
        }
    });

    /**
     * Method to initiate deletion of the stack
     */
    function delete_cloudformation() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'delete_cloudformation',
            },
            beforeSend: function() {
                $("#setup_progress_div").hide();
                $("#stack_management_div").hide();
                $("#delete_stack_options_div").hide();
                $('#desc_text_cf_in_progress_div').hide();

                disable_retry_buttons();

                $('#stack_deletion_success').hide();
                $('#stack_deletion_failure').hide();
                $('#stack_deletion_in_progress').show();

                $("#stack_deletion_progress_div").show();
            },
            success: function(response) {
                if (response['success']) {
                    if (response['data'] == 'NotConfirmed') {} else {
                        $('#setup_error_div').hide();
                        $('#retry_delete_stack_div').hide();
                        setTimeout(function() {
                            check_stack_deletion();
                        }, WAIT_TIME_IN_MS);
                    }
                } else {
                    var message = "There was an error deleting the stack. Try again.";
                    $('#setup_error_text').html(message);
                    $('#setup_error_response').html(response['data']);
                    $('#setup_error_response_wrapper').show();

                    $('#stack_deletion_success').hide();
                    $('#stack_deletion_failure').show();
                    $('#stack_deletion_in_progress').hide();

                    display_retry_button('delete_stack');
                    $('#setup_error_div').show();
                }
            }
        });
    }

    /**
     * Retries stack deletion by calling delete_cloudformation
     */
    $('#retry_delete_stack_div').on('click', '#retry_delete_stack_btn', function(e) {
        e.preventDefault();
        delete_cloudformation();
    });

    /**
     * Initiates restart by deleting stack
     */
    $('#restart_setup_div').on('click', '#restart_setup_btn', function(e) {
        e.preventDefault();
        var confirmation_text = "Restarting will delete all created resources. Are you sure you wanted to proceed?";
        if (confirm(confirmation_text)) {
            $("#setup_progress_div").hide();
            delete_cloudformation();
        }
    });

    /**
     * Polls stack status during deletion of stack
     */
    function check_stack_deletion() {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'check_stack_deletion',
            },
            beforeSend: function() {
                disable_retry_buttons();
                $('#retry_check_stack_deletion_btn').prop('disabled', true);

                $('#stack_deletion_success').hide();
                $('#stack_deletion_failure').hide();
                $('#stack_deletion_in_progress').show();
            },
            success: function(response) {
                if (response['success']) {
                    if (response['data'] == stack_responses.DELETED) {
                        location.reload();
                    } else {
                        $('#stack_deletion_info').html(response['data']);

                        $('#stack_deletion_success').hide();
                        $('#stack_deletion_failure').hide();
                        $('#stack_deletion_in_progress').show();

                        setTimeout(function() {
                            check_stack_deletion();
                        }, WAIT_TIME_IN_MS);
                    }
                } else {
                    var message = "There was an error during the deletion process. Try again.";
                    $('#setup_error_text').html(message);
                    $('#setup_error_response').html(response['data']);
                    $('#setup_error_response_wrapper').show();
                    display_retry_button('check_stack_deletion');

                    $('#stack_deletion_success').hide();
                    $('#stack_deletion_failure').show();
                    $('#stack_deletion_in_progress').hide();

                    $('#setup_error_div').show();
                }
            }
        });
    }

    /**
     * Retries check of stack status during deletion
     */
    $('#retry_check_stack_deletion_div').on('click', '#retry_check_stack_deletion_btn', function(e) {
        e.preventDefault();
        delete_cloudformation('', true);
    });
    /**
     * Method to resume UI from creation/deletion if previously initiated
     */
    $(document).ready(function($) {
        $.ajax({
            url: amazon_cf_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'get_stack_state',
            },
            success: function(response) {
                if (response['data'] == stack_states.CREATING) {
                    display_setup_state('acm_cert', 'in_progress');
                    $('#setup_progress_div').show();
                    display_dv_dns_mapping();
                } else if (response['data'] == stack_states.CERT_CREATED) {
                    display_setup_state('acm_cert', 'success');
                    $('#setup_progress_div').show();
                    update_cloudformation_stack();
                } else if (response['data'] == stack_states.UPDATING) {
                    display_setup_state('acm_cert', 'success');
                    display_setup_state('cf_dist', 'in_progress');

                    $('#setup_progress_div').show();
                    check_cf_creation();
                } else if (response['data'] == stack_states.VALIDATING) {
                    display_setup_state('acm_cert', 'success');
                    display_setup_state('cf_dist', 'success');
                    display_setup_state('cdn_alias_dns', 'pending');

                    $('#setup_progress_div').show();
                    display_cdn_alias_dns_mapping();
                } else if (response['data'] == stack_states.DELETING) {
                    $('#stack_deletion_progress_div').show();
                    check_stack_deletion();
                }
            }
        });
    });
});