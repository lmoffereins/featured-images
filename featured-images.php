<?php

/**
 * The Featured Images Plugin
 * 
 * @package Featured Images
 * @subpackage Main
 */

/**
 * Plugin Name:       Featured Images
 * Description:       Attach multiple featured images to posts or other objects
 * Plugin URI:        https://github.com/lmoffereins/featured-images/
 * Version:           1.0.1
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       featured-images
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/featured-images
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Featured_Images' ) && ! function_exists( 'featured_images' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class Featured_Images {

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @return The single Featured_Images
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Featured_Images;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version      = '1.0.1';
		$this->db_version   = 20180927;

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Assets
		$this->assets_dir   = trailingslashit( $this->plugin_dir . 'assets' );
		$this->assets_url   = trailingslashit( $this->plugin_url . 'assets' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass();
		$this->domain       = 'featured-images';
	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'update.php'    );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Scripts & Customizer
		add_action( 'wp_loaded',           array( $this, 'register_scripts'    )        );
		add_filter( 'media_view_settings', array( $this, 'media_settings'      ), 10, 2 );
		add_action( 'customize_register',  array( $this, 'customizer_register' ),  5    );

		// Post
		add_action( 'add_meta_boxes',              array( $this, 'post_add_metabox'         ), 10, 2 );
		add_action( 'registered_post_type',        array( $this, 'registered_post_type'     ), 10, 2 );
		add_action( 'wp_ajax_set_featured_images', array( $this, 'ajax_set_featured_images' )        );

		// Admin
		if ( is_admin() ) {
			add_action( 'admin_init', 'featured_images_setup_updater', 999 );
		}
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/featured-images/' . $mofile;

		// Look in global /wp-content/languages/featured-images folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/featured-images/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/** Public methods **************************************************/

	/**
	 * Register plugin scripts
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'featured_images_media_l10n'
	 * @uses apply_filters() Calls 'featured_images_editor_l10n'
	 */
	public function register_scripts() {

		// Media
		wp_register_script( 'featured-images-media', $this->assets_url . 'js/featured-images-media.js', array( 'jquery', 'media-models', 'media-views' ), $this->version, true );
		wp_localize_script( 'featured-images-media', 'featuredImagesMedia', apply_filters( 'featured_images_media_l10n', array(
			'l10n' => array(
				'frameTitle'  => esc_html__( 'Select images', 'featured-images' ),
				'frameButton' => esc_html__( 'Choose images', 'featured-images' ),
			),
		) ) );

		// Customizer
		wp_register_script( 'featured-images-customizer', $this->assets_url . 'js/featured-images-customizer.js', array( 'jquery', 'customize-controls', 'featured-images-media' ), $this->version, true );

		// Editor
		wp_register_script( 'featured-images-editor', $this->assets_url . 'js/featured-images-editor.js', array( 'jquery', 'featured-images-media' ), $this->version, true );
	}

	/**
	 * Modify the post's media settings to add its featured images
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Media settings
	 * @param WP_Post $post Post object
	 * @return array Media settings
	 */
	public function media_settings( $settings, $post ) {

		// Add featured images to the post's media settings
		if ( is_a( $post, 'WP_Post' ) && post_type_supports( $post->post_type, 'featured-images' ) ) {
			$images = get_featured_images( $post );
			$settings['post']['featuredImages'] = $images ? $images : -1;
		}

		return $settings;
	}

	/**
	 * Load Customizer logic
	 * 
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manger $wp_customize
	 */
	public function customizer_register( $wp_customize ) {

		// Load control type class
		require_once( $this->includes_dir . 'classes/class-customize-featured-images-control.php' );

		// Register Featured Images control type
		$wp_customize->register_control_type( 'Customize_Featured_Images_Control' );
	}

	/**
	 * Add plugin post type support when the post type is registered
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'featured_images_add_post_type_support'
	 *
	 * @param string $post_type Post type name
	 * @param WP_Post_Type $post_type_object Post type object
	 */
	public function registered_post_type( $post_type, $post_type_object ) {
		global $wp_post_types;

		// Bail when plugin support is already declared
		if ( post_type_supports( $post_type, 'featured-images' ) )
			return;

		// Add support for all post types that already support post thumbnails
		$add_support = ( 'attachment' !== $post_type ) && post_type_supports( $post_type, 'thumbnail' );

		// Bail when no support is desired. Enable plugin filtering.
		if ( ! apply_filters( 'featured_images_add_post_type_support', $add_support, $post_type ) )
			return;

		// Add post type support
		add_post_type_support( $post_type, 'featured-images' );

		// Add extra post type labels
		$post_type_object->labels->featured_images     = esc_html_x( 'Featured Images',     'Post type label', 'featured-images' );
		$post_type_object->labels->set_featured_images = esc_html_x( 'Set featured images', 'Post type label', 'featured-images' );

		// Update global post type object
		$wp_post_types[ $post_type ] = $post_type_object;
	}

	/**
	 * Register the Featured Images post metabox
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type name
	 * @param WP_Post $post Post object
	 */
	public function post_add_metabox( $post_type, $post ) {

		// Bail when the post type does not support Featured Images
		if ( ! post_type_supports( $post_type, 'featured-images' ) )
			return;

		$post_type_object = get_post_type_object( $post_type );

		// Remove default featured image metabox
		remove_meta_box( 'postimagediv', null, 'side' );

		// Register plugin metabox
		add_meta_box( 'featured-images', $post_type_object->labels->featured_images, 'featured_images_post_metabox', null, 'side', 'default', null );

		// Enqueue scripts
		wp_enqueue_script( 'featured-images-editor' );
		wp_localize_script( 'featured-images-editor', 'featuredImagesEditor', apply_filters( 'featured_images_editor_l10n', array(
			'l10n' => array(
				'frameTitle'  => $post_type_object->labels->featured_images,
				'frameButton' => $post_type_object->labels->set_featured_images,
			),
			'settings' => array(
				'minWidth'  => 0,
				'minHeight' => 0,
				'maxWidth'  => 9999,
				'maxHeight' => 9999,
			),
		) ) );

		// Append styles
		wp_add_inline_style( 'wp-admin', "
			#featured-images .current { margin: 8px 0; }
			#featured-images .upload-button { white-space: normal; width: 50%; height: auto; float: " . ( is_rtl() ? 'left' : 'right' ) . "; }
			#featured-images .current .container { overflow: hidden; -webkit-border-radius: 2px; border: 1px solid transparent; -webkit-border-radius: 2px; border-radius: 2px; min-height: 40px; }
			#featured-images .placeholder { width: 100%; position: relative; text-align: center; cursor: default; }
			#featured-images .inner { display: none; position: absolute; width: 100%; color: #555; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: block; min-height: 40px; line-height: 20px; top: 10px; }
			#featured-images .actions { margin-bottom: 0px; }
			#featured-images .attachment-media-view { width: 24.25%; margin: 0 1% 1% 0; float: left; }
			#featured-images .attachment-media-view:nth-child(4n) { margin-right: 0; }
			#featured-images .thumbnail-image { line-height: 0; }
			#featured-images img { cursor: pointer; max-width: 100%; -webkit-border-radius: 2px; border-radius: 2px; }
			#featured-images .thumbnail-more { display: block; max-width: 150px; height: inherit; padding: inherit; -webkit-border-radius: 2px; border-radius: 2px; box-shadow: none; }
			#featured-images .thumbnail-more:before { content: ''; display: inline-block; padding-top: 100%; vertical-align: middle; }
			#featured-images .thumbnail-more .more-count { display: inline-block; width: 100%; vertical-align: middle; margin-left: -4px; text-align: center; font-size: 2em; cursor: pointer; }
			"
		);
	}

	/** Ajax ************************************************************/

	/**
	 * Update an object's featured images through AJAX
	 *
	 * @see wp_ajax_set_post_thumbnail()
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'featured_images_ajax_check_referer'
	 *
	 * @param int $object_id Object ID
	 * @param string $object_type Object type
	 * @param array|int $images Attachment ids or '-1'
	 */
	public function ajax_set_featured_images() {
		$json = ! empty( $_REQUEST['json'] ); // New-style request

		// Handle posts by default
		$object_id   = intval( $_POST['object_id'] );
		$object_type = isset( $_POST['object_type'] ) ? $_POST['object_type'] : 'post_type';

		// Check post type referers
		if ( 'post_type' === $object_type ) {
			if ( ! $post = get_post( $object_id ) )
				wp_die( -1 );
			if ( ! current_user_can( 'edit_post', $object_id ) )
				wp_die( -1 );

			if ( $json ) {
				check_ajax_referer( "update-post_$object_id" );
			} else {
				check_ajax_referer( "set_featured_images-$object_id" );
			}
		}

		// Bail when the ajax referer does not check out
		if ( ! apply_filters( 'featured_images_ajax_check_referer', true, $object_id, $object_type ) )
			return;

		$images = array_map( 'intval', (array) $_POST['images'] );

		// Delete featured images
		if ( '-1' == $_POST['images'] ) {
			if ( set_featured_images( array(), $object_id, $object_type ) ) {
				$return = $this->ajax_get_return_message( $object_id, $object_type );
				$json ? wp_send_json_success( $return ) : wp_die( $return );
			} else {
				wp_die( 0 );
			}
		}

		// Update featured images
		if ( set_featured_images( $images, $object_id, $object_type ) ) {
			$return = $this->ajax_get_return_message( $object_id, $object_type );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		}

		wp_die( 0 );
	}

	/**
	 * Return the AJAX return message
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'featured_images_ajax_get_return_message'
	 *
	 * @param int $object Object ID
	 * @param string $object_type Optional. Object type. Defaults to 'post_type'.
	 * @return mixed AJAX return message
	 */
	public function ajax_get_return_message( $object, $object_type = 'post_type' ) {

		// Define return variable
		$retval = '';

		switch ( $object_type ) {
			case 'post_type' :

				// Return new metabox layout
				$retval = featured_images_post_metabox( $object, false );

				break;
		}

		return apply_filters( 'featured_images_ajax_get_return_message', $retval, $object, $object_type );
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return Featured_Images
 */
function featured_images() {
	return Featured_Images::instance();
}

// Initiate
featured_images();

endif; // class_exists
