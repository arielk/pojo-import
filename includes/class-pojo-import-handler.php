<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Import_Handler extends WP_Import {

	const PLACEHOLDER_SLUG = 'pojo-placeholder';

	protected $placeholder_image_id = null;
	
	protected function _get_placeholder_from_media() {
		global $wpdb;
		
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `ID` FROM %1$s
					WHERE `post_name` = \'%2$s\'
						AND `post_type` = \'attachment\'
				;',
				$wpdb->posts,
				self::PLACEHOLDER_SLUG
			)
		);
		
		if ( ! is_null( $post_id ) )
			return $post_id;
		
		return false;
	}

	public function process_attachment( $post, $url ) {
		if ( is_null( $this->placeholder_image_id ) ) {
			$post_id = $this->_get_placeholder_from_media();
			if ( $post_id ) {
				$this->placeholder_image_id = $post_id;
				return $this->placeholder_image_id;
			}
			
			if ( ! function_exists( 'WP_Filesystem' ) )
				require_once ABSPATH . 'wp-admin/includes/file.php';
			
			global $wp_filesystem;
			
			WP_Filesystem();
			$upload = wp_upload_bits(
				'pojo-placeholder.png',
				null,
				$wp_filesystem->get_contents( POJO_IMPORT_ASSETS_PATH . 'images/placeholder.png' )
			);

			$info = wp_check_filetype( $upload['file'] );
			if ( $info )
				$post['post_mime_type'] = $info['type'];
			else
				return new WP_Error( 'attachment_processing_error', __( 'Invalid file type', 'pojo-import' ) );
			
			$post['post_title'] = self::PLACEHOLDER_SLUG;
			$post['post_name'] = self::PLACEHOLDER_SLUG;
			
			$post['guid'] = $upload['url'];
			$post_id      = wp_insert_attachment( $post, $upload['file'] );
			wp_update_attachment_metadata(
				$post_id,
				wp_generate_attachment_metadata( $post_id, $upload['file'] )
			);

			$this->placeholder_image_id = $post_id;
		}
		
		return $this->placeholder_image_id;
	}
	
}