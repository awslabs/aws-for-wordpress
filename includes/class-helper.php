<?php
/**
 * The file that defines custom helper functions
 *
 * @link       amazon.com
 * @since      4.0.0
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/includes
 */
class Helper {
    public function log_error($filename, $message) {
        date_default_timezone_set('UTC');
        error_log('['.date("F j, Y, H:i:s.v e").'] '.$message."\n", 3,  $filename);
    }

    public function validate_url($url) {
    	return preg_match('/^(\*\.)?(((?!-)[A-Za-z0-9-]{0,62}[A-Za-z0-9])\.)+((?!-)[A-Za-z0-9-]{1,62}[A-Za-z0-9])$/',$url);
    } 
}