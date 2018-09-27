<?php

/**
 * Featured Images Customizer Control
 * 
 * @package Featured Images
 * @subpackage Customizer
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Customize_Featured_Images_Control' ) ) :
/**
 * Customize Featured Images Control
 *
 * A customizer control to select multiple images.
 *
 * @since 1.0.0
 */
class Customize_Featured_Images_Control extends WP_Customize_Image_Control {

	/**
	 * The type of customize control being rendered.
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $type = 'featured_images';

	/**
	 * Holds all selected images
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $images = array();

	/**
	 * Minimum width for selectable images.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	public $min_width = 0;

	/**
	 * Minimum height for selectable images.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	public $min_height = 0;

	/**
	 * Maximum width for selectable images.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	public $max_width = 0;

	/**
	 * Maximum height for selectable images.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	public $max_height = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		$default_labels = array(
			'select'       => esc_html__( 'Select images', 'featured-images' ),
			'change'       => esc_html__( 'Change images', 'featured-images' ),
			'default'      => esc_html__( 'Default', 'featured-images' ),
			'remove'       => esc_html__( 'Remove all', 'featured-images' ),
			'placeholder'  => esc_html__( 'No images selected', 'featured-images' ),
			'frame_title'  => esc_html__( 'Select images', 'featured-images' ),
			'frame_button' => esc_html__( 'Choose images', 'featured-images' )
		);

		// Ensure that the labels contain all required default values.
		$args = wp_parse_args( $args, array( 'button_labels' => array() ) );
		$args['button_labels'] = array_merge( (array) $default_labels, (array) $args['button_labels'] );

		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Enqueue styles/scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		parent::enqueue();

		wp_enqueue_script( 'featured-images-customizer' );

		/**
		 * Mimic styles for media handling controls 
		 * 
		 * @see wp-admin/css/customize-controls.css
		 */
		wp_add_inline_style( 'customize-controls', "
			.customize-control-{$this->type} .current { margin: 8px 0; }
			.customize-control-{$this->type} .upload-button { white-space: normal; width: 48%; height: auto; float: " . ( is_rtl() ? 'left' : 'right' ) . "; }
			.customize-control-{$this->type} .current .container { overflow: hidden; -webkit-border-radius: 2px; border: 1px solid transparent; -webkit-border-radius: 2px; border-radius: 2px; min-height: 40px; }
			.customize-control-{$this->type} .placeholder { width: 100%; position: relative; text-align: center; cursor: default; }
			.customize-control-{$this->type} .inner { display: none; position: absolute; width: 100%; color: #555; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: block; min-height: 40px; line-height: 20px; top: 10px; }
			.customize-control-{$this->type} .actions { margin-bottom: 0px; }
			.customize-control-{$this->type} .attachment-media-view { width: 24.25%; margin: 0 1% 1% 0; float: left; }
			.customize-control-{$this->type} .attachment-media-view:nth-child(4n) { margin-right: 0; }
			.customize-control-{$this->type} img { -webkit-border-radius: 2px; border-radius: 2px; }
			.customize-control-{$this->type} .thumbnail-more { display: block; height: inherit; max-width: 150px; padding: inherit; -webkit-border-radius: 2px; border-radius: 2px; box-shadow: none; }
			.customize-control-{$this->type} .thumbnail-more:before { content: ''; display: inline-block; padding-top: 100%; vertical-align: middle; }
			.customize-control-{$this->type} .thumbnail-more .more-count { display: inline-block; width: 100%; vertical-align: middle; margin-left: -4px; text-align: center; font-size: 2em; cursor: pointer; }
			"
		);
	}

	/**
	 * Refresh the parameters passed to the Javascript via JSON
	 *
	 * @see WP_Customize_Control::to_json()
	 *
	 * @since 1.0.0
	 */
	public function to_json() {

		// Skip WP_Customize_Media_Control::to_json() since it prevents
		// us from using an array value to return from ::value().
		WP_Customize_Control::to_json();

		// WP_Customize_Media_Control
		$this->json['label'] = html_entity_decode( $this->label, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$this->json['mime_type'] = $this->mime_type;
		$this->json['button_labels'] = $this->button_labels;
		$this->json['canUpload'] = current_user_can( 'upload_files' );

		// Add custom params
		$this->json['minWidth']  = absint( $this->min_width );
		$this->json['minHeight'] = absint( $this->min_height );
		$this->json['maxWidth']  = absint( $this->max_width );
		$this->json['maxHeight'] = absint( $this->max_height );

		// Get the selected attachments
		$value = $this->get_current_attachments();

		// Prepare attachments and return in json
		if ( is_object( $this->setting ) && $value ) {
			$attachments = array_filter( array_map( 'wp_prepare_attachment_for_js', $value ) );

			if ( ! empty( $attachments ) ) {
				$this->json['attachments'] = $attachments;
			}
		}
	}

	/**
	 * Render a JS template for the content of the media control.
	 *
	 * @since 1.0.0
	 */
	public function content_template() {
		?>
		<label for="{{ data.settings['default'] }}-button">
			<# if ( data.label ) { #>
				<span class="customize-control-title">{{ data.label }}</span>
			<# } #>
			<# if ( data.description ) { #>
				<span class="description customize-control-description">{{{ data.description }}}</span>
			<# } #>
		</label>

		<# if ( ! _.isEmpty( data.attachments ) ) { #>
			<div class="current">
				<div class="container">

					<# _.find( data.attachments, function( att, i ) { #>
					<div class="attachment-media-view attachment-media-view-{{ att.type }} {{ att.orientation }}">
						<div class="thumbnail thumbnail-{{ att.type }}">
							<# if ( att.sizes && att.sizes.thumbnail ) { #>
								<img class="attachment-thumb" src="{{ att.sizes.thumbnail.url }}" draggable="false" />
							<# } else { #>
								<img class="attachment-thumb" src="{{ att.sizes.full.url }}" draggable="false" />
							<# } #>
						</div>
					</div>

					<# if ( 10 == i && data.attachments.length > 12 ) { #>
					<div class="attachment-media-view attachment-media-view-more">
						<div class="thumbnail thumbnail-more button-primary">
							<span class="more-count">+{{ data.attachments.length - 11 }}</span>
						</div>
					</div>
					<# return true; }; #>

					<# }); #>
				</div>
			</div>
			<div class="actions">
				<# if ( data.canUpload ) { #>
				<button type="button" class="button remove-button"><?php echo $this->button_labels['remove']; ?></button>
				<button type="button" class="button upload-button" id="{{ data.settings['default'] }}-button"><?php echo $this->button_labels['change']; ?></button>
				<div style="clear:both"></div>
				<# } #>
			</div>
		<# } else { #>
			<div class="current">
				<div class="container">
					<div class="placeholder">
						<div class="inner">
							<span>
								<?php echo $this->button_labels['placeholder']; ?>
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class="actions">
				<# if ( data.canUpload ) { #>
				<button type="button" class="button upload-button" id="{{ data.settings['default'] }}-button"><?php echo $this->button_labels['select']; ?></button>
				<# } #>
				<div style="clear:both"></div>
			</div>
		<# } #>
		<?php
	}

	/**
	 * Return the currently selected attachment ids
	 *
	 * @since 1.0.0
	 *
	 * @return array Selected attachment ids
	 */
	public function get_current_attachments() {

		// Define return variable
		$attachments = array();

		// Get the selected attachment ids
		foreach ( (array) $this->value() as $attachment_id ) {
			$attachments[] = ! is_numeric( $attachment_id ) ? attachment_url_to_postid( $attachment_id ) : (int) $attachment_id;
		}

		return $attachments;
	}
}

endif; // class_exists
