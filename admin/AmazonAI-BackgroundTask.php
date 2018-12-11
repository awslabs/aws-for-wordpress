<?php
/**
 * Class for running an action in the background.
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

class AmazonAI_BackgroundTask {


  const ADMIN_POST_ACTION = 'amazon_polly_run_background_task';

  /**
   * Trigger an action in the background
   *
   * Triggers a background action by making an HTTP call to the local server and not waiting for a response.
   * Similar to how WP triggers cron events in wp-includes/cron.php.
   *
   * @see https://developer.wordpress.org/reference/hooks/admin_post_action/ Fires on an authenticated admin post request for the given action
   * @see https://developer.wordpress.org/reference/hooks/https_local_ssl_verify/ Filters whether SSL should be verified for local requests
   * @see https://developer.wordpress.org/reference/classes/WP_Http/request/ Documents args used by `wp_remote_post(...)`
   * @see https://developer.wordpress.org/reference/functions/apply_filters/ To filter `https_local_ssl_verify`
   * @see https://developer.wordpress.org/reference/functions/admin_url/ Generate an admin URL
   * @see https://developer.wordpress.org/reference/functions/wp_create_nonce/ Create a cryptographic token
   * @see https://developer.wordpress.org/reference/functions/wp_remote_post/ Used to make an HTTP call to this site
   *
   * @param string $task Task to be called in the background
   *
   * @return bool True if http request to trigger background task is successful, false otherwise
   */
  public function trigger($task, $args = []) {
    $url = admin_url('admin-post.php');

    $request_args = [
      'timeout' => 0.01,
      'blocking' => false,
      /** This filter is documented in WordPress Core wp-includes/class-wp-http-streams.php */
      'sslverify' => apply_filters('https_local_ssl_verify', false),
      'body' => [
        'nonce' => wp_create_nonce($this->nonce_action_for_task($task)),
        'action' => self::ADMIN_POST_ACTION,
        'task' => $task,
        'args' => json_encode($args),
      ],
      'headers' => [
        'cookie' => implode('; ', $this->get_cookies()),
      ],
    ];

    $logger = new AmazonAI_Logger();
    $logger->log(sprintf('%s Triggering background task %s', __METHOD__, $task));

    return wp_remote_post($url, $request_args);
  }

  /**
   * Run task as a WP action
   *
   * @see https://developer.wordpress.org/reference/functions/__/ Localize string
   * @see https://developer.wordpress.org/reference/functions/do_action_ref_array/ Run action
   * @see https://developer.wordpress.org/reference/functions/wp_die/ Kill request and display message
   * @see https://developer.wordpress.org/reference/functions/wp_verify_nonce/ Verify cryptographic token
   */
  public function run() {
    $task = (array_key_exists('task', $_POST)) ? trim($_POST['task']) : '';
    $args = (array_key_exists('args', $_POST)) ? json_decode($_POST['args']) : [];

    if ( empty($task) ) {
      error_log(sprintf('%s Invalid background task. Missing task.', __METHOD__));
      wp_die(__('Invalid background task.', 'amazon-polly'), 'Invalid Request', 400);
    }

    if ( ! is_array($args) ) {
      error_log(sprintf('%s Invalid background task args.', __METHOD__));
      wp_die(__('Invalid background task args.', 'amazon-polly'), 'Invalid Request', 400);
    }

    if ( ! isset($_POST['nonce']) || 1 !== wp_verify_nonce($_POST['nonce'], $this->nonce_action_for_task($task)) ) {
      error_log(sprintf('%s Expired background task request for task %s', __METHOD__, $task));
      wp_die(__('Expired background task request.', 'amazon-polly'), 'Expired Request', 403);
    }

    $logger = new AmazonAI_Logger();
    $logger->log(sprintf('%s Running background task %s', __METHOD__, $task));

    /**
     * Fires when running a background task
     *
     * The dynamic portion of the hook name, `$task`, refers to the task
     * that being run.
     */
    do_action_ref_array(sprintf('amazon_polly_background_task_%s', $task), $args);
  }

  /**
   * Return current user's cookies to authenticate a background request as the current user
   *
   * @return array Sanitized cookies
   */
  private function get_cookies() {
    $cookies = [];

    foreach ( $_COOKIE as $name => $value ) {
      $sanitized_value = is_array($value) ? serialize($value) : $value;
      $sanitized_value = urlencode($sanitized_value);

      $cookies[] = sprintf("%s=%s", $name, $sanitized_value);
    }

    return $cookies;
  }

  /**
   * Generate nonce action name for task
   *
   * @param $task
   *
   * @return string
   */
  private function nonce_action_for_task($task) {
    return sprintf('%s:%s', self::ADMIN_POST_ACTION, $task);
  }
}
