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
	 * Holds internally enqueued scripts
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $enqueue = array();

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses Featured_Images::setup_globals()
	 * @uses Featured_Images::setup_actions()
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

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

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
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Scripts
		add_action( 'wp_loaded', array( $this, 'register_scripts' ) );

		// UI
		add_action( 'customize_register', array( $this, 'customizer'       ),  5    );
		add_action( 'add_meta_boxes',     array( $this, 'post_add_metabox' ), 10, 2 );

		// Media
		add_filter( 'media_view_settings', array( $this, 'media_settings' ), 10, 2 );

		// Ajax
		add_action( 'wp_ajax_set_featured_images', array( $this, 'ajax_set_featured_images' ) );
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
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
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
	 * @uses wp_register_script()
	 * @uses wp_localize_script()
	 * @uses wp_enqueue_script()
	 */
	public function register_scripts() {

		// Media
		wp_register_script( 'featured-images-media', $this->includes_url . 'assets/js/featured-images-media.js', array( 'jquery', 'media-models', 'media-views' ), $this->version, true );
		wp_localize_script( 'featured-images-media', 'featuredImagesMedia', apply_filters( 'featured_images_media_l10n', array(
			'l10n' => array(
				'frameTitle'  => __( 'Select Images', 'featured-images' ),
				'frameButton' => __( 'Choose Images', 'featured-images' ),
			),
		) ) );

		// Customizer
		wp_register_script( 'featured-images-customizer', $this->includes_url . 'assets/js/featured-images-customizer.js', array( 'jquery', 'customize-controls', 'featured-images-media' ), $this->version, true );

		// Editor
		wp_register_script( 'featured-images-editor', $this->includes_url . 'assets/js/featured-images-editor.js', array( 'jquery', 'featured-images-media' ), $this->version, true );
		wp_localize_script( 'featured-images-editor', 'featuredImagesEditor', apply_filters( 'featured_images_editor_l10n', array(
			'l10n' => array(
				'frameTitle' => __( 'Select Featured Images', 'featured-images' ),
			),
			'settings' => array(
				'minWidth'  => 0,
				'minHeight' => 0,
				'maxWidth'  => 9999,
				'maxHeight' => 9999,
			),
		) ) );
	}

	/**
	 * Modify the post's media settings to add its featured images
	 *
	 * @since 1.0.0
	 *
	 * @uses Featured_Images::post_type_supports()
	 * @uses get_featured_images()
	 *
	 * @param array $settings Media settings
	 * @param WP_Post $post Post object
	 * @return array Media settings
	 */
	public function media_settings( $settings, $post ) {

		// Add featured images to the post's media settings
		if ( is_a( $post, 'WP_Post' ) && $this->post_type_supports( $post->post_type ) ) {
			$images = get_featured_images( $post, 'post' );
			$settings['post']['featuredImages'] = $images ? $images : -1;
		}

		return $settings;
	}

	/**
	 * Load Customizer logic
	 * 
	 * @since 1.0.0
	 *
	 * @uses WP_Customize_Manger::register_control_type()
	 * @param WP_Customize_Manger $wp_customize
	 */
	public function customizer( $wp_customize ) {

		// Register Featured Images control type
		require_once( $this->includes_dir . 'class-customize-featured-images-control.php' );
		$wp_customize->register_control_type( 'Customize_Featured_Images_Control' );
	}

	/**
	 * Return whether the given post type supports Featured Images
	 *
	 * @since 1.0.0
	 *
	 * @uses is_post_type_viewable() WP 4.4+
	 * @uses get_post_type_object()
	 *
	 * @param string $post_type Optional. Post type.
	 * @return bool Post type supports featured images
	 */
	public function post_type_supports( $post_type = '' ) {

		// Default to the current post type
		if ( empty( $post_type ) ) {
			$post_type = get_post_type();
		}

		// Bail for attachments
		if ( 'attachment' == $post_type )
			return false;

		// Base support on viewability
		$supports = is_post_type_viewable( get_post_type_object( $post_type ) );

		return $supports;
	}

	/**
	 * Register the Featured Images post metabox
	 *
	 * @since 1.0.0
	 *
	 * @uses Featured_Images::post_type_supports()
	 * @uses add_meta_box()
	 * @uses wp_enqueue_script()
	 * @uses wp_add_inline_style()
	 *
	 * @param string $post_type Post type name
	 * @param WP_Post $post Post object
	 */
	public function post_add_metabox( $post_type, $post ) {

		// Bail when the post type does not support Featured Images
		if ( ! $this->post_type_supports( $post_type ) )
			return;

		// Register metabox
		add_meta_box( 'featured-images', __( 'Featured Images', 'featured-images' ), 'featured_images_post_metabox', null, 'side', 'default', null );

		// Enqueue scripts
		wp_enqueue_script( 'featured-images-editor' );

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
	 * @since 1.0.0
	 *
	 * @see wp_ajax_set_post_thumbnail()
	 *
	 * @uses check_ajax_referer()
	 * @uses set_featured_images()
	 * @uses Featured_Images::ajax_get_return_message()
	 * @uses wp_send_json_success()
	 *
	 * @param int $object_id Object ID
	 * @param string $type Object type
	 * @param array|int $images Attachment ids or '-1'
	 */
	public function ajax_set_featured_images() {
		$json = ! empty( $_REQUEST['json'] ); // New-style request

		$object_id = intval( $_POST['object_id'] );
		$type      = $_POST['type'];

		switch ( $type ) {
			case 'post' :
			default :
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

		$images = array_map( 'intval', (array) $_POST['images'] );

		// Delete featured images
		if ( '-1' == $_POST['images'] ) {
			if ( set_featured_images( array(), $object_id, $type ) ) {
				$return = $this->ajax_get_return_message( $object_id, $type );
				$json ? wp_send_json_success( $return ) : wp_die( $return );
			} else {
				wp_die( 0 );
			}
		}

		// Update featured images
		if ( set_featured_images( $images, $object_id, $type ) ) {
			$return = $this->ajax_get_return_message( $object_id, $type );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		}

		wp_die( 0 );
	}

	/**
	 * Return the AJAX return message
	 *
	 * @since 1.0.0
	 *
	 * @uses featured_images_post_metabox()
	 *
	 * @param int $object Object ID
	 * @param string $type Object type
	 * @return mixed AJAX return message
	 */
	public function ajax_get_return_message( $object, $type = 'post' ) {

		switch ( $type ) {
			case 'post' :
			default :
				$retval = featured_images_post_metabox( $object, false );
		}

		return $retval;
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
