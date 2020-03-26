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

class AmazonAI_S3FileHandler extends AmazonAI_FileHandler {
  	private $s3_client;
  
	/**
	 * @var AmazonAI_Common
	 */
	private $common;

	/**
	 * AmazonAI_S3FileHandler constructor.
	 *
	 * @param AmazonAI_Common $common
	 */
	public function __construct(AmazonAI_Common $common) {
		$this->common = $common;
	}

  /**
	 * Return type of storage which is supported by class (S3).
	 *
	 * @since    2.1.0
	 */
    public function get_type() {
      return "s3";
    }

    public function set_s3_client($new_s3_client) {
      $this->s3_client = $new_s3_client;
    }

    /**
  	 * Function responsible for saving file on local storage file system.
  	 *
  	 * @param           $wp_filesystem         Not used here.
  	 * @param           $file                  File name.
  	 * @param           $post_id               ID of the post.
  	 * @since           2.0.3
  	 */
    public function delete($wp_filesystem, $file, $post_id) {

      $common = $this->common;

      // Retrieve the name of the bucket where audio files are stored.
      $s3_bucket  = $this->get_bucket_name();
      $prefix     = $this->get_prefix($post_id);

      // Delete main audio file.
      $this->delete_s3_object( $s3_bucket, $prefix . $file );

      // Delete translations if available.
      foreach ( $common->get_all_polly_languages() as $language_code ) {
        $value = get_post_meta( $post_id, 'amazon_polly_translation_' . $language_code, true );
        if ( ! empty( $value ) ) {
          $s3_key = $prefix . 'amazon_polly_' . $post_id . $language_code . '.mp3';
          $this->delete_s3_object( $s3_bucket, $s3_key );
        }
      }

    }


    /**
  	 * Function responsible for saving file on local storage file system.
  	 *
  	 * @param           $wp_filesystem         Not used here.
  	 * @param           $file_temp_full_name   Temporary name of file on local filesystem.
  	 * @param           $dir_final_full_name   Final destination where file should be saved.
  	 * @param           $file_final_full_name  Final name of file.
  	 * @param           $post_id               ID of the post.
  	 * @param           $file_name             Name of the file.
  	 * @since           2.0.3
  	 */
    public function save($wp_filesystem, $file_temp_full_name, $dir_final_full_name, $file_final_full_name, $post_id, $file_name) {
        $media_library_att_id = get_post_meta( $post_id, 'amazon_polly_media_library_attachment_id', true );
  			if ( !empty($media_library_att_id) ) {
  				wp_delete_attachment( $media_library_att_id, true );
  			}

        $key = $this->get_prefix($post_id) . $file_name;

  			// We are storing audio file on Amazon S3.
  			$s3BucketName = $this->get_bucket_name();
  			$audio_location = 's3';
  			$result         = $this->s3_client->putObject(
  				array(
  					'ACL'        => 'public-read',
  					'Bucket'     => $s3BucketName,
  					'Key'        => $key,
  					'SourceFile' => $file_temp_full_name,
  				)
  			);
  			$wp_filesystem->delete( $file_temp_full_name );

        return $this->get_s3_object_link($post_id, $file_name);
    }

    public function get_s3_object_link($post_id, $file_name) {

      $s3BucketName = $this->get_bucket_name();
      $cloudfront_domain_name = apply_filters('amazon_polly_cloudfront_domain', get_option( 'amazon_polly_cloudfront' ));
      $key = $this->get_prefix($post_id) . $file_name;

      if ( empty( $cloudfront_domain_name ) ) {

        $common = $this->common;
        $selected_region = $common->get_aws_region();

        $audio_location_link = 'https://s3.' . $selected_region . '.amazonaws.com/' . $s3BucketName . '/' . $key;
      } else {
        $audio_location_link = 'https://' . $cloudfront_domain_name . '/' . $key;
      }

      return $audio_location_link;

    }

    public function check_if_s3_bucket_accessible() {

      $s3BucketName    = $this->get_bucket_name();

      // Check if user specified bucket name in using filter.
      $s3BucketName = apply_filters( 'amazon_polly_s3_bucket_name', $s3BucketName );

      //Check if bucket is provided and can be access.
      if ( empty( $s3BucketName ) ) {
        return false;
      } else {
        try {
          $result = $this->s3_client->headBucket(array('Bucket' => $s3BucketName));
        } catch ( Aws\S3\Exception\S3Exception $e ) {
          throw new S3BucketNotAccException('S3 Bucket not Accessible');
        }
      }

      return true;
    }

    public function create_s3_bucket() {

      $logger = new AmazonAI_Logger();
      $logger->log(sprintf('%s Creating new S3 Bucket', __METHOD__));

      $createNewBucket = true;

      // If bucket was not provided (or was not accessible), we need to create new bucket.
      // We will try to do it 10 times.
      for ( $i = 0; $i <= 10; $i++ ) {
        if ( $createNewBucket ) {
          try {

            $rand1 = wp_rand( 10000000000, 99999999999 );
            $rand2 = md5( microtime() );
            $name  = 'audio-for-wordpress-' . $rand1 . $rand2;
            $name  = substr( $name, 0, 60 );

            $result = $this->s3_client->createBucket( array( 'Bucket' => $name ) );
  					update_option( 'amazon_polly_s3_bucket', $name );
  					$createNewBucket = false;

            $logger->log(sprintf('%s New S3 Bucket created ( name=%s )', __METHOD__, $name));

  				} catch ( Aws\S3\Exception\S3Exception $e ) {
            $logger->log(sprintf('%s Failed to Create new S3 Bucket! ( error=%s )', __METHOD__, $e));
            error_log($e);
  					update_option( 'amazon_polly_s3_bucket', '' );
  					update_option( 'amazon_polly_s3', '' );

            throw new S3BucketNotCreException('Could not create S3 Bucket');
  				}
        }
  		}

    }


        /**
         * Delets object from S3.
         *
         * @param string $post_id ID of the post for which audio should be deleted.
         * @since 2.0.0
         */
        private function delete_s3_object( $bucket, $key ) {

          $this->s3_client->deleteObject(
            array(
              'Bucket' => $bucket,
              'Key'    => $key,
            )
          );

        }


        /**
         * Get S3 bucket name. The method uses filter 'amazon_polly_s3_bucket_name,
         * which allows to use customer S3 bucket name instead of default one.
         *
         * @since  1.0.6
         */
        public function get_bucket_name() {

          $s3BucketName = get_option( 'amazon_polly_s3_bucket' );
          $s3BucketName = apply_filters( 'amazon_polly_s3_bucket_name', $s3BucketName );

          return $s3BucketName;
        }

}
