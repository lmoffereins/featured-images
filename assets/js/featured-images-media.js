/**
 * Featured Images Media scripts
 *
 * Contains the logic for a custom Media Library interface
 *
 * @package Featured Images
 * @subpackage Media
 */

/* global wp, jQuery, featuredImagesMedia */
( function( wp, $ ) {
	var media = wp.media,
	    Select = media.view.MediaFrame.Select,
	    Library = media.controller.Library,
	    Attachments = media.model.Attachments,
	    l10n = media.view.l10n;

	/**
	 * Custom implementation of the Select view
	 *
	 * @since 1.0.0
	 *
	 * @see wp.media.view.MediaFrame.Post
	 * 
	 * @class
	 * @augments wp.media.MediaFrame.Select
	 * @augments wp.media.MediaFrame
	 * @augments wp.media.Frame
	 * @augments wp.media.View
	 */
	media.view.MediaFrame.FeaturedImagesFrame = Select.extend({

		/**
		 * Extend handler bindings
		 *
		 * @since 1.0.0
		 */
		bindHandlers: function() {
			Select.prototype.bindHandlers.apply( this, arguments );

			// Add toolbar 'edit-selection'
			this.on( 'toolbar:render:select', this.selectionStatusToolbar, this );

			// Display edit-selection window
			this.on( 'content:render:edit-selection', this.editSelectionContent, this );
		},

		/**
		 * Display the edit-selection state media browser
		 *
		 * @since 1.0.0
		 */
		editSelectionContent: function() {
			var state = this.state(),
				selection = state.get('selection'),
				view;

			view = new media.view.AttachmentsBrowser({
				controller: this,
				collection: selection,
				selection:  selection,
				model:      state,
				sortable:   true,
				search:     false,
				date:       false,
				dragInfo:   true,

				AttachmentView: media.view.Attachments.EditSelection
			}).render();

			view.toolbar.set( 'backToLibrary', {
				text:     l10n.returnToLibrary,
				priority: -100,

				click: function() {
					this.controller.content.mode('browse');
				}
			});

			// Browse our library of attachments.
			this.content.set( view );

			// Trigger the controller to set focus
			this.trigger( 'edit:selection', this );
		},

		/**
		 * Enable selection status toolbar
		 *
		 * @since 1.0.0
		 * 
		 * @param {wp.Backbone.View} view
		 */
		selectionStatusToolbar: function( view ) {
			var editable = this.state().get('editable');

			view.set( 'selection', new media.view.Selection({
				controller: this,
				collection: this.state().get('selection'),
				priority:   -40,

				// If the selection is editable, pass the callback to
				// switch the content mode.
				editable: editable && function() {
					this.controller.content.mode('edit-selection');
				}
			}).render() );
		}
	});

	/**
	 * Custom implementation of the Library controller
	 *
	 * @since 1.0.0
	 * 
	 * @see wp.media.controller.FeaturedImage
	 * 
	 * @class
	 * @augments wp.media.controller.Library
	 * @augments wp.media.controller.State
	 * @augments Backbone.Model
	 */
	media.controller.FeaturedImagesLibrary = Library.extend({

		/**
		 * Define controller defaults
		 *
		 * @since 1.0.0
		 */
		defaults: _.defaults({
			title: featuredImagesMedia.l10n.frameTitle
		}, Library.prototype.defaults ),

		/**
		 * Listen for the library's selection updates
		 *
		 * @since 1.0.0
		 */
		initialize: function() {
			var library, comparator;
		
			Library.prototype.initialize.apply( this, arguments );

			library = this.get('library');
			comparator = library.comparator;

			// Overload the library's comparator to push items that are not in
			// the mirrored query to the front of the aggregate collection.
			library.comparator = function( a, b ) {
				var aInQuery = !! this.mirroring.get( a.cid ),
					bInQuery = !! this.mirroring.get( b.cid );

				if ( ! aInQuery && bInQuery ) {
					return -1;
				} else if ( aInQuery && ! bInQuery ) {
					return 1;
				} else {
					return comparator.apply( this, arguments );
				}
			};

			// Add all items in the selection to the library, so any selected
			// images that are not initially loaded still appear.
			library.observe( this.get('selection') );
		},

		/**
		 * Add event listening when activating
		 *
		 * @since 1.0.0
		 */
		activate: function() {
			// Update the library's selection when activating
			this.updateSelection();
			this.frame.on( 'open', this.updateSelection, this );

			Library.prototype.activate.apply( this, arguments );
		},

		/**
		 * Remove event listening when deactivating
		 *
		 * @since 1.0.0
		 */
		deactivate: function() {
			this.frame.off( 'open', this.updateSelection, this );

			Library.prototype.deactivate.apply( this, arguments );
		},

		/**
		 * Update the library's current selection
		 *
		 * @since 1.0.0
		 */
		updateSelection: function() { /* Needs overwriting */ }
	});

	/**
	 * Construct implementation of the FeaturedImagesLibrary modal controller
	 * for the Customizer context.
	 *
	 * @since 1.0.0
	 *
	 * @see wp.media.controller.FeaturedImagesLibrary
	 *
	 * @class
	 * @augments wp.media.controller.FeaturedImagesLibrary
	 * @augments wp.media.controller.Library
	 * @augments wp.media.controller.State
	 * @augments Backbone.Model
	 */
	media.controller.FeaturedImagesCustomizerLibrary = media.controller.FeaturedImagesLibrary.extend({

		/**
		 * Overload the controller's native selection updater method
		 *
		 * @since 1.0.0
		 */
		updateSelection: function() {
			var selection = this.frame.state().get('selection'),
			    control = this.get('control'),
				attachment;

			_.each( _.pluck( control.params.attachments, 'id' ), function( id ) {
				attachment = media.attachment( id );
				attachment.fetch();

				selection.add( attachment ? [ attachment ] : [] );
			});
		}
	})

	/**
	 * Custom implementation of the Query model
	 *
	 * @since 1.0.0
	 *
	 * @augments wp.media.model.Query
	 * @augments wp.media.model.Attachments
	 * @augments Backbone.Collection
	 */
	media.model.FeaturedImagesQuery = Attachments.extend({

		/**
		 * Extend the media item validator by checking for proper dimensions
		 *
		 * @since 1.0.0
		 */
		validator: function( attachment ) {
			// First let's run the default validator
			var valid = media.model.Attachments.prototype.validator.apply( this, arguments ),
			    props = this.props.attributes;

			// Check for min/max dimensions
			if ( valid && props.minWidth ) 
				valid = props.minWidth <= attachment.attributes.width;
			if ( valid && props.minHeight )
				valid = props.minHeight <= attachment.attributes.height;
			if ( valid && props.maxWidth )
				valid = props.maxWidth >= attachment.attributes.width;
			if ( valid && props.maxHeight )
				valid = props.maxHeight >= attachment.attributes.height;

			return valid;
		}
	});

})( wp, jQuery );
