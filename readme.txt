=== Featured Images ===
Contributors: Offereins
Tags: featured, images, multiple
Requires at least: 4.4
Tested up to: 4.9.8
Stable tag: 1.0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Attach multiple featured images to posts or other objects.

== Description ==

This plugin replaces the 'Featured Image' post metabox with one for selecting multiple Featured Images in your (custom) post (type)'s edit page. Using the Media Library you can attach any number of images to the post. The plugin uses the default post thumbnail data structure (metakey `_thumbnail_id`), so your default post thumbnail logic still works, just with the first image of your selection.

To display multiple images in your theme, use `get_featured_images()`. For an example, see the following code:

```
// Make sure the plugin is available
if ( function_exists( 'featured_images' ) ) {

	// When not in The Loop, provide a post ID to the function.
	foreach ( get_featured_images( $post_id ) as $attachment_id ) {

		// Display the image, for example using `wp_get_attachment_image( $attachment_id )`
	}
}
```

== Installation ==

You can download and install Featured Images using the built in WordPress plugin installer. If you download Featured Images manually, make sure it is uploaded to "/wp-content/plugins/featured-images/".

Activate Featured Images in the "Plugins" admin panel using the "Activate" link. If you're using WordPress Multisite, you can choose to activate Featured Images network wide for full integration with all of your sites.

== Changelog ==

= 1.1.0 =
* Use the same data structure for both single and multiple featured images for posts (metakey `_thumbnail_id`)
* Define and detect plugin post type support using standard WP functions, like `post_type_supports()`
* Added various filters for adding 3rd party logic
* Added Dutch translations

= 1.0.1 =
* Fixed using the right script handle to add inline editor styles
* Limit display of images in metabox/image container to 12. Excess selection is shown as '+2'

= 1.0.0 =
* Initial release
