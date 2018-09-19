# Featured Images #

Attach multiple featured images to posts or other objects.

## Description ##

> This WordPress plugin requires at least [WordPress](https://wordpress.org) 4.4.

This plugin adds an additional 'Featured Images' metabox feature - note the extra 's' - to your (custom) post (type)'s edit page. Using the Media Library you can attach any number of images to the post. To display the images in your theme, use `get_featured_images()`. For an example, see the following code:

```
// Make sure the plugin is available
if ( function_exists( 'featured_images' ) ) {
	// When not in The Loop, provide a post ID to the function.
	foreach ( get_featured_images() as $attachment_id ) {
		// Display the image, for example using `wp_get_attachment_image( $attachment_id )`
	}
}
```

## Installation ##

If you download Featured Images manually, make sure it is uploaded to "/wp-content/plugins/featured-images/".

Activate Featured Images in the "Plugins" admin panel using the "Activate" link.

## Updates ##

This plugin is not hosted in the official WordPress repository. Instead, updating is supported through use of the [GitHub Updater](https://github.com/afragen/github-updater/) plugin by @afragen and friends.

## Contributing ##

You can contribute to the development of this plugin by [opening a new issue](https://github.com/lmoffereins/featured-images/issues/) to report a bug or request a feature in the plugin's GitHub repository.
