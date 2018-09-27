<?php

/**
 * Featured Images Functions
 * 
 * @package Featured Images
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the plugin version
 *
 * @since 1.1.0
 */
function featured_images_version() {
	echo featured_images_get_version();
}

	/**
	 * Return the plugin version
	 *
	 * @since 1.1.0
	 *
	 * @return string The plugin version
	 */
	function featured_images_get_version() {
		return featured_images()->version;
	}

/**
 * Output the plugin database version
 *
 * @since 1.1.0
 */
function featured_images_db_version() {
	echo featured_images_get_db_version();
}

	/**
	 * Return the plugin database version
	 *
	 * @since 1.1.0
	 *
	 * @return string The plugin version
	 */
	function featured_images_get_db_version() {
		return featured_images()->db_version;
	}

/**
 * Output the plugin database version directly from the database
 *
 * @since 1.1.0
 */
function featured_images_db_version_raw() {
	echo featured_images_get_db_version_raw();
}

	/**
	 * Return the plugin database version directly from the database
	 *
	 * @since 1.1.0
	 *
	 * @return string The current plugin version
	 */
	function featured_images_get_db_version_raw() {
		return get_option( 'featured_images_db_version', '' );
	}

/** Get|Set *******************************************************************/

/**
 * Return the object's featured images
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'get_featured_images'
 *
 * @param int|WP_Post $object Optional. Object ID or post object. Defaults to current post.
 * @param string $object_type Optional. Object type. Defaults to 'post_type'.
 * @return array|false Set of attachment ids or false when object was not found.
 */
function get_featured_images( $object = null, $object_type = 'post_type' ) {

	// Define default variable
	$images = array();

	// Switch object type
	switch ( $object_type ) {
		case 'post_type' :

			// Images are stored as post meta
			if ( $post = get_post( $object ) ) {
				$images = get_post_meta( $post->ID, '_thumbnail_id', false );
			}

			break;
	}

	return (array) apply_filters( 'get_featured_images', $images, $object, $object_type );
}

/**
 * Redefine the object's featured images
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'set_featured_images'
 *
 * @param int|array $images Optional. New featured images. Attachment ID or array thereof.
 *                          Defaults to an empty array which effectively deletes all references.
 * @param int|WP_Post $object Optional. Object ID or post object. Defaults to current post.
 * @param string $object_type Optional. Object type. Defaults to 'post_type'.
 * @return bool Update success.
 */
function set_featured_images( $images = array(), $object = null, $object_type = 'post_type' ) {

	// Define return variable
	$retval = false;
	$images = (array) $images;

	switch ( $object_type ) {
		case 'post_type' :

			// Get the post
			if ( $post = get_post( $object ) ) {

				// Remove all current references
				delete_post_meta( $post->ID, '_thumbnail_id' );

				// Define new featured images
				if ( ! empty( $images ) ) {
					foreach ( $images as $attachment ) {

						// Skip when the attachment is not valid
						if ( ! $attachment = get_post( $attachment ) )
							continue;

						add_post_meta( $post->ID, '_thumbnail_id', $attachment->ID );
					}
				}

				// If we made it to here, the update succeeded.
				$retval = true;
			}

			break;
	}

	return (bool) apply_filters( 'set_featured_images', $retval, $images, $object, $object_type );
}

/** Admin *********************************************************************/

/**
 * Display the contents of the Featured Images post metabox
 *
 * @since 1.0.0
 *
 * @param WP_Post $post Post object
 * @param bool $echo Optional. Whether to echo the metabox content.
 */
function featured_images_post_metabox( $post, $echo = true ) {

	// Get the post's Post Images
	$images = array_filter( array_map( 'wp_prepare_attachment_for_js', get_featured_images( $post ) ) );

	// Start output buffer when not echoing
	if ( ! $echo ) {
		ob_start();
	}

	// With or without images
	if ( ! empty( $images ) ) : ?>

	<div class="current">
		<div class="container">

			<?php foreach ( $images as $i => $attachment ) : ?>
			<div class="attachment-media-view attachment-media-view-<?php echo $attachment['type']; ?> <?php echo $attachment['orientation']; ?>">
				<div class="thumbnail thumbnail-<?php echo $attachment['type']; ?>">
					<?php if ( $attachment['sizes'] && isset( $attachment['sizes']['thumbnail'] ) ) : ?>
						<img class="attachment-thumb" src="<?php echo $attachment['sizes']['thumbnail']['url']; ?>" draggable="false" />
					<?php else : ?>
						<img class="attachment-thumb" src="<?php echo $attachment['sizes']['full']['url']; ?>" draggable="false" />
					<?php endif; ?>
				</div>
			</div>

			<?php // Do not display more than 12 (3 * 4) thumbs ?>
			<?php if ( 10 == $i && count( $images ) > 12 ) : ?>
			<div class="attachment-media-view attachment-media-view-more">
				<div class="thumbnail thumbnail-more button-primary">
					<span class="more-count">+<?php echo ( count( $images ) - 11 ); ?></span>
				</div>
			</div>
			<?php break; endif; ?>

			<?php endforeach; ?>
		</div>
	</div>
	<div class="actions">
		<?php if ( current_user_can( 'upload_files' ) ) : ?>
		<button type="button" class="button remove-button"><?php esc_html_e( 'Remove all', 'featured-images' ); ?></button>
		<button type="button" class="button upload-button" id="featured-images-button"><?php esc_html_e( 'Change images', 'featured-images' ); ?></button>
		<div style="clear:both"></div>
		<?php endif; ?>
	</div>

	<?php else : ?>

	<div class="current">
		<div class="container">
			<div class="placeholder">
				<div class="inner">
					<span>
						<?php esc_html_e( 'No images selected', 'featured-images' ); ?>
					</span>
				</div>
			</div>
		</div>
	</div>
	<div class="actions">
		<?php if ( current_user_can( 'upload_files' ) ) : ?>
		<button type="button" class="button upload-button" id="featured-images-button"><?php esc_html_e( 'Select images', 'featured-images' ); ?></button>
		<?php endif; ?>
		<div style="clear:both"></div>
	</div>

	<?php endif;

	// Return output buffer
	if ( ! $echo ) {
		return ob_get_clean();
	}
}
