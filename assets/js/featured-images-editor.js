/**
 * Featured Images Editor scripts
 *
 * @package Featured Images
 * @subpackage Media
 */

/* global wp, featuredImagesMedia, featuredImagesEditor */
( function( $, _ ) {
	var FeaturedImagesLibrary = wp.media.controller.FeaturedImagesLibrary,
	    l10n = Object.assign( {}, featuredImagesMedia.l10n, featuredImagesEditor.l10n );

	/**
	 * Construct implementation of the FeaturedImagesLibrary modal controller
	 * for the Featured Images metabox
	 *
	 * @since 1.0.0
	 */
	wp.media.controller.FeaturedImages = FeaturedImagesLibrary.extend({

		/**
		 * Overload the controller's native selection updater method
		 *
		 * @since 1.0.0
		 */
		updateSelection: function() {
			var selection = this.frame.state().get('selection'),
				attachment;

			// Featured Images are stored in the view settings' post global
			_.each( wp.media.view.settings.post.featuredImages, function( id ) {
				attachment = wp.media.attachment( id );
				attachment.fetch();

				selection.add( attachment ? [ attachment ] : [] );
			});
		}
	});

	/**
	 * wp.media.FeaturedImages
	 * @namespace
	 *
	 * @see wp.media.featuredImage wp-includes/js/media-editor.js
	 */
	wp.media.FeaturedImages = {

		/**
		 * Get the attachment ids
		 *
		 * @global wp.media.view.settings
		 *
		 * @returns {wp.media.view.settings.post.featuredImages|array}
		 */
		get: function() {
			return wp.media.view.settings.post.featuredImages;
		},

		/**
		 * Set the attachment ids, save the attachment data and
		 * set the HTML in the post meta box to the new selection.
		 *
		 * @global wp.media.view.settings
		 * @global wp.media.post
		 *
		 * @param {array|int} ids The attachment ids of the Featured Images, or -1 to unset it.
		 */
		set: function( ids ) {
			var settings = wp.media.view.settings;

			settings.post.featuredImages = ids;

			wp.media.post( 'set_featured_images', {
				json:      true,
				object_id: settings.post.id,
				type:      'post',
				images:    settings.post.featuredImages,
				_wpnonce:  settings.post.nonce
			}).done( function( html ) {
				$( '.inside', '#featured-images' ).html( html );
			});
		},

		/**
		 * The Featured Images workflow
		 *
		 * @global wp.media.controller.FeaturedImage
		 * @global wp.media.view.l10n
		 *
		 * @this wp.media.FeaturedImages
		 *
		 * @returns {wp.media.view.MediaFrame.Select} A media workflow.
		 */
		frame: function() {
			if ( this._frame ) {
				wp.media.frame = this._frame;
				return this._frame;
			}

			// Setup the media modal frame
			this._frame = new wp.media.view.MediaFrame.FeaturedImagesFrame({
				button: {
					text: l10n.frameButton
				},
				states: [

					// Display the media browser state
					new wp.media.controller.FeaturedImages({
						title: l10n.frameTitle,

						// Query the requested media items
						library: new wp.media.model.FeaturedImagesQuery( null, {
							props: _.extend({
								type:     'image',
								orderby:  'date',

								// Custom query params
								minWidth:  featuredImagesEditor.settings.minWidth,
								minHeight: featuredImagesEditor.settings.minHeight,
								maxWidth:  featuredImagesEditor.settings.maxWidth,
								maxHeight: featuredImagesEditor.settings.maxHeight
							}, {
								query: true
							})
						}),

						// Enable multi-select mode
						multiple: 'add',
						search: true,
						editable: true
					})
				]
			});

			// When the selection in the library is confirmed, run a callback
			this._frame.state('library').on( 'select', this.select );

			return this._frame;
		},

		/**
		 * 'select' callback for Featured Images workflow, triggered when
		 * the 'Choose Images' button is clicked in the media modal.
		 *
		 * @global wp.media.view.settings
		 *
		 * @this wp.media.controller.FeaturedImages
		 */
		select: function() {
			var selection = this.get('selection').toJSON();

			if ( ! wp.media.view.settings.post.featuredImages ) {
				return;
			}

			wp.media.FeaturedImages.set( selection ? _.pluck( selection, 'id' ) : -1 );
		},

		/**
		 * Open the content media manager to the 'Featured Images' tab when
		 * in the Featured Images metabox is clicked.
		 *
		 * Update the post image ids when the 'remove' link is clicked.
		 *
		 * @global wp.media.view.settings
		 */
		init: function() {
			$( '#featured-images' ).on( 'click', '.thumbnail, .upload-button', function( event ) {
				event.preventDefault();
				event.stopPropagation();

				wp.media.FeaturedImages.frame().open();
			}).on( 'click', '.remove-button', function() {
				wp.media.FeaturedImages.set( -1 );
			});
		}
	};

	$( wp.media.FeaturedImages.init );

}( jQuery, _ ) );
