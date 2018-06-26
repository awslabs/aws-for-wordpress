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

class AmazonAI_LocalFileHandler extends AmazonAI_FileHandler {


  /**
	 * Return type of storage which is supported by class (local).
	 *
	 * @since    2.0.3
	 */
    public function get_type() {
      return "local";
    }

    /**
  	 * Function responsible for saving file on local storage file system.
  	 *
  	 * @param           $wp_filesystem         Reference to WP filesystem.
  	 * @param           $file                  File name.
  	 * @param           $translate_langs       Not used here.
  	 * @param           $post_id               ID of the post.
  	 * @since           2.0.3
  	 */
    public function delete($wp_filesystem, $file, $translate_langs, $post_id) {

      // Getting full file path.
      $upload_dir       = trailingslashit( wp_upload_dir()['basedir'] );
      $prefix           = $this->get_prefix($post_id);
      $file_full_path   = $upload_dir . $prefix . $file;

      // Deleting file.
      $wp_filesystem->delete( $file_full_path );

      // Deleting media library attachment.
      $media_library_att_id = get_post_meta( $post_id, 'amazon_polly_media_library_attachment_id', true );
      wp_delete_attachment( $media_library_att_id, true );

    }

    /**
  	 * Function responsible for saving file on local storage file system.
  	 *
  	 * @param           $wp_filesystem         Reference to WP filesystem.
  	 * @param           $file_temp_full_name   Temporary name of file on local filesystem.
  	 * @param           $dir_final_full_name   Final destination where file should be saved.
  	 * @param           $file_final_full_name  Final name of file.
  	 * @param           $post_id               ID of the post.
  	 * @param           $file_name             Name of the file.
  	 * @since           2.0.3
  	 */
    public function save($wp_filesystem, $file_temp_full_name, $dir_final_full_name, $file_final_full_name, $post_id, $file_name) {

        // Creating directories based on full path of file.
        if ( ! $wp_filesystem->is_dir( $dir_final_full_name ) ) {
          wp_mkdir_p( $dir_final_full_name );
        }

        // We are storing audio file on the WP server.
        // Moving file to it's final location and deleting temporary file.
        $wp_filesystem->move( $file_temp_full_name, $file_final_full_name, true );
        $wp_filesystem->delete( $file_temp_full_name );

        // Creating final link to the file
        $audio_location_link = trailingslashit(wp_upload_dir()['baseurl']) . $this->get_prefix($post_id) . $file_name;

        // Adding audio info to media library.
        $this->add_media_library( $file_final_full_name, $post_id );

        return $audio_location_link;

    }

    /**
  	 * Adding information about audio to media library
  	 *
  	 * @param           $post_id       Id of the post.
  	 * @param           $filename      Path to file.
  	 * @since           2.0.3
  	 */
  	private function add_media_library( $filename, $post_id ) {

  		// The ID of the post this attachment is for.
  		$parent_post_id = $post_id;

  		// Check the type of file. We'll use this as the 'post_mime_type'.
  		$filetype = wp_check_filetype( basename( $filename ), null );

  		// Get the path to the upload directory.
  		$wp_upload_dir = wp_upload_dir();

  		// Prepare an array of post data for the attachment.
  		$attachment = array(
  			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
  			'post_mime_type' => $filetype['type'],
  			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
  			'post_content'   => '',
  			'post_status'    => 'inherit',
  		);

  		// Insert the attachment.
  		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

  		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
  		require_once ABSPATH . 'wp-admin/includes/image.php';

  		// Generate the metadata for the attachment, and update the database record.
  		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
  		wp_update_attachment_metadata( $attach_id, $attach_data );

  		update_post_meta( $post_id, 'amazon_polly_media_library_attachment_id', $attach_id );

  	}

}
