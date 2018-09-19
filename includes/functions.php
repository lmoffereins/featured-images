<?php

/**
 * Featured Images Functions
 * 
 * @package Featured Images
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the object's featured images
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'get_featured_images'
 *
 * @param int|WP_Post $object Optional. Object ID or post object. Defaults to current post.
 * @param string $type Optional. Object type. Defaults to 'post'.
 * @return array|false Set of attachment ids or false when object was not found.
 */
function get_featured_images( $object = null, $type = 'post' ) {

	// Define default variable
	$images = array();

	// Switch object type
	switch ( $type ) {

		// Default to 'post'
		case 'post' :
		default :

			// Bail when the post is not valid
			if ( ! $object = get_post( $object ) )
				return false;

			// Images are stored as post meta
			$images = get_post_meta( $object->ID, 'featured-images', false );
	}

	return (array) apply_filters( 'get_featured_images', $images, $object, $type );
}

/**
 * Redefine the object's featured images
 *
 * @since 1.0.0
 *
 * @param int|array $images Optional. New featured images. Attachment ID or array thereof.
 *                          Defaults to an empty array which effectively deletes all references.
 * @param int|WP_Post $object Optional. Object ID or post object. Defaults to current post.
 * @param string $type Optional. Object type. Defaults to 'post'.
 * @return bool Update success.
 */
function set_featured_images( $images = array(), $object = null, $type = 'post' ) {

	switch ( $type ) {
		case 'post' :
		default :

			// Bail when the post is not valid
			if ( ! $object = get_post( $object ) )
				return false;

			// Remove all current references
			delete_post_meta( $object->ID, 'featured-images' );

			// Define new featured images
			if ( ! empty( $images ) ) {
				foreach ( (array) $images as $attachment ) {

					// Skip when the attachment is not valid
					if ( ! $attachment = get_post( $attachment ) )
						continue;

					add_post_meta( $object->ID, 'featured-images', $attachment->ID );
				}
			}
	}

	// If we made it to here, the update succeeded.
	return true;
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
		<button type="button" class="button remove-button"><?php _e( 'Remove All', 'featured-images' ); ?></button>
		<button type="button" class="button upload-button" id="featured-images-button"><?php _e( 'Change Selection', 'featured-images' ); ?></button>
		<div style="clear:both"></div>
		<?php endif; ?>
	</div>

	<?php else : ?>

	<div class="current">
		<div class="container">
			<div class="placeholder">
				<div class="inner">
					<span>
						<?php _e( 'No images selected', 'featured-images' ); ?>
					</span>
				</div>
			</div>
		</div>
	</div>
	<div class="actions">
		<?php if ( current_user_can( 'upload_files' ) ) : ?>
		<button type="button" class="button upload-button" id="featured-images-button"><?php _e( 'Select Images', 'featured-images' ); ?></button>
		<?php endif; ?>
		<div style="clear:both"></div>
	</div>

	<?php endif;

	// Return output buffer
	if ( ! $echo ) {
		return ob_get_clean();
	}
}
