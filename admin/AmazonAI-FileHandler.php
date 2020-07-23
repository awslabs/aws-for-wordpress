<?php
/**
 *
 *
 * @link       amazon.com
 * @since      2.0.3
 *
 * @package    Amazonpolly
 * @subpackage Amazonpolly/admin
 */

abstract class AmazonAI_FileHandler {

    abstract public function save($wp_filesystem, $file_temp_full_name, $dir_final_full_name, $file_final_full_name, $post_id, $file_name);
    abstract public function delete($wp_filesystem, $file, $post_id);
    abstract public function get_type();

    protected function get_prefix($post_id) {
      if ( get_option('uploads_use_yearmonth_folders') ) {
        $prefix = get_the_date( 'Y', $post_id ) . '/' . get_the_date( 'm', $post_id ) . '/';
      } else {
        $prefix = '';
      }

      /**
       * Filters the file prefix used to generate the file path
       *
       * @param string $prefix The file prefix
       */
      return apply_filters('amazon_polly_file_prefix', $prefix);
    }
}
