<?php
/**
 * Asset enqueue for the frontend, plus the shared editor config builder.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'loom_enqueue_frontend' );

/**
 * Enqueue the base frontend stylesheet and runtime on builder pages.
 *
 * @return void
 */
function loom_enqueue_frontend() {
	$post_id = get_queried_object_id();

	// Load on builder entries, or whenever an active header/footer template exists.
	$has_template = function_exists( 'loom_get_active_templates' )
		&& ( loom_get_active_templates( 'header' ) || loom_get_active_templates( 'footer' ) );

	$load_everywhere = function_exists( 'loom_settings_get' ) && loom_settings_get( 'load_everywhere', 0 );

	if ( ! $load_everywhere && ( ! $post_id || ! loom_is_enabled( $post_id ) ) && ! $has_template ) {
		return;
	}

	wp_enqueue_style(
		'loom-frontend',
		LOOM_URL . 'assets/css/frontend.css',
		array( 'dashicons' ),
		LOOM_VERSION
	);

	// Runtime for sliders/carousels/animations/lightbox (used from Phase 2 on).
	wp_enqueue_script(
		'loom-frontend',
		LOOM_URL . 'assets/js/frontend.js',
		array(),
		LOOM_VERSION,
		true
	);
}

/**
 * Build the config object handed to the editor JS (widget schema, endpoints,
 * media/breakpoint info). Single source of truth comes from the registry.
 *
 * @param int $post_id Post being edited.
 * @return array
 */
function loom_get_editor_config( $post_id ) {
	$registry = \Loom\Builder\Registry::instance();

	$config = array(
		'postId'      => (int) $post_id,
		'restUrl'     => esc_url_raw( rest_url( 'loom/v1' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'previewUrl'  => esc_url_raw( get_preview_post_link( $post_id ) ),
		'adminUrl'    => esc_url_raw( admin_url( 'admin.php' ) ),
		'templatesUrl'   => esc_url_raw( admin_url( 'edit.php?post_type=loom_template' ) ),
		'newTemplateUrl' => esc_url_raw( admin_url( 'post-new.php?post_type=loom_template' ) ),
		'widgets'      => $registry->editor_schema(),
		'breakpoints'  => loom_breakpoints(),
		'dynamicText'  => function_exists( 'loom_dynamic_field_choices' ) ? loom_dynamic_field_choices() : array(),
		'dynamicImage' => function_exists( 'loom_dynamic_image_choices' ) ? loom_dynamic_image_choices() : array(),
		'categories'  => array(
			'layout'      => __( 'Layout', 'loom' ),
			'basic'       => __( 'Basic', 'loom' ),
			'media'       => __( 'Media', 'loom' ),
			'site'        => __( 'Site', 'loom' ),
			'woocommerce' => __( 'WooCommerce', 'loom' ),
		),
		'i18n'        => array(
			'save'              => __( 'Save', 'loom' ),
			'saved'             => __( 'Saved', 'loom' ),
			'saving'            => __( 'Saving...', 'loom' ),
			'exit'              => __( 'Exit', 'loom' ),
			'addSection'        => __( 'Add Section', 'loom' ),
			'content'           => __( 'Content', 'loom' ),
			'style'             => __( 'Style', 'loom' ),
			'advanced'          => __( 'Advanced', 'loom' ),
			'widgets'           => __( 'Widgets', 'loom' ),
			'search'            => __( 'Search widget...', 'loom' ),
			'empty'             => __( 'Drag a widget here', 'loom' ),
			'delete'            => __( 'Delete', 'loom' ),
			'duplicate'         => __( 'Duplicate', 'loom' ),
			'desktop'           => __( 'Desktop', 'loom' ),
			'tablet'            => __( 'Tablet', 'loom' ),
			'mobile'            => __( 'Mobile', 'loom' ),
			'selectMedia'       => __( 'Select image', 'loom' ),
			// Inspector / canvas chrome.
			'section'           => __( 'Section', 'loom' ),
			'column'            => __( 'Column', 'loom' ),
			'row'               => __( 'Row', 'loom' ),
			'selectElement'     => __( 'Select an element to edit it.', 'loom' ),
			'adjustLayout'      => __( 'Adjust layout in the Style tab.', 'loom' ),
			'pageEmpty'         => __( 'Your page is empty.', 'loom' ),
			'dragMove'          => __( 'Drag to move', 'loom' ),
			'exportSection'     => __( 'Export section', 'loom' ),
			'undo'              => __( 'Undo', 'loom' ),
			'redo'              => __( 'Redo', 'loom' ),
			'importSections'    => __( 'Import sections', 'loom' ),
			'exportPage'        => __( 'Export page', 'loom' ),
			'invalidFile'       => __( 'Invalid layout file.', 'loom' ),
			'unsaved'          => __( 'Unsaved', 'loom' ),
			'unsavedChanges'   => __( 'You have unsaved changes. Leave without saving?', 'loom' ),
			'saveError'        => __( 'Could not save. Please try again.', 'loom' ),
			'error'            => __( 'Error', 'loom' ),
			'moveUp'            => __( 'Up', 'loom' ),
			'moveDown'          => __( 'Down', 'loom' ),
			'dynamicField'      => __( 'Dynamic field', 'loom' ),
			'staticValue'       => __( '— Static —', 'loom' ),
			// Style panel.
			'padding'           => __( 'Padding', 'loom' ),
			'margin'            => __( 'Margin', 'loom' ),
			'background'        => __( 'Background', 'loom' ),
			'backgroundImage'   => __( 'Background image', 'loom' ),
			'textAlign'         => __( 'Text align', 'loom' ),
			'left'              => __( 'Left', 'loom' ),
			'center'            => __( 'Center', 'loom' ),
			'right'             => __( 'Right', 'loom' ),
			'textColor'         => __( 'Text color', 'loom' ),
			'fontSize'          => __( 'Font size', 'loom' ),
			'fontWeight'        => __( 'Font weight', 'loom' ),
			'minHeight'         => __( 'Min height', 'loom' ),
			'radius'            => __( 'Radius', 'loom' ),
			'maxWidth'          => __( 'Max width', 'loom' ),
			'contentWidth'      => __( 'Content width', 'loom' ),
			'contentWidthDefault' => __( 'Site default', 'loom' ),
			'contentWidthPx'    => __( 'Custom (px)', 'loom' ),
			'contentWidthPct'   => __( 'Custom (%) / full width', 'loom' ),
			'columnWidth'       => __( 'Column width % (0 = auto)', 'loom' ),
			'columnBasis'       => __( 'Column basis', 'loom' ),
			'flexGrow'          => __( 'Flex grow', 'loom' ),
			'flexShrink'        => __( 'Flex shrink', 'loom' ),
			'unitPx'            => __( 'px', 'loom' ),
			'unitPercent'       => __( '%', 'loom' ),
			'unitVw'            => __( 'vw', 'loom' ),
			'unitVh'            => __( 'vh', 'loom' ),
			'unitAuto'          => __( 'Auto', 'loom' ),
			'clear'             => __( 'Clear', 'loom' ),
			'bgSize'            => __( 'Background size', 'loom' ),
			'bgSizeCover'       => __( 'Cover', 'loom' ),
			'bgSizeContain'     => __( 'Contain', 'loom' ),
			'bgSizeAuto'        => __( 'Auto', 'loom' ),
			'contentJustify'    => __( 'Content vertical alignment', 'loom' ),
			'contentValign'     => __( 'Content horizontal alignment', 'loom' ),
			'columnsGap'        => __( 'Columns gap', 'loom' ),
			'columnsLayout'     => __( 'Columns layout', 'loom' ),
			'columnsRow'        => __( 'Row', 'loom' ),
			'columnsStack'      => __( 'Stack', 'loom' ),
			'columnsHorizontal' => __( 'Columns horizontal alignment', 'loom' ),
			'columnsVertical'   => __( 'Columns vertical alignment', 'loom' ),
			'horizontalAlign'   => __( 'Horizontal alignment', 'loom' ),
			'verticalAlign'     => __( 'Vertical alignment', 'loom' ),
			'alignStart'        => __( 'Start', 'loom' ),
			'alignEnd'          => __( 'End', 'loom' ),
			'alignSpaceBetween' => __( 'Space between', 'loom' ),
			'alignStretch'      => __( 'Stretch', 'loom' ),
			'alignTop'          => __( 'Top', 'loom' ),
			'alignBottom'       => __( 'Bottom', 'loom' ),
			// Advanced panel.
			'none'              => __( 'None', 'loom' ),
			'cssId'             => __( 'CSS ID', 'loom' ),
			'cssClasses'        => __( 'CSS Classes', 'loom' ),
			'entranceAnimation' => __( 'Entrance animation', 'loom' ),
			'duration'          => __( 'Duration (ms)', 'loom' ),
			'delay'             => __( 'Delay (ms)', 'loom' ),
			'easing'            => __( 'Easing', 'loom' ),
			'loopAnimation'     => __( 'Loop animation', 'loom' ),
			'hoverAnimation'    => __( 'Hover animation', 'loom' ),
			'hideDesktop'       => __( 'Hide on desktop', 'loom' ),
			'hideTablet'        => __( 'Hide on tablet', 'loom' ),
			'hideMobile'        => __( 'Hide on mobile', 'loom' ),
			// Topbar: header/footer quick access and the "More tools" overflow menu.
			'headerFooter'      => __( 'Header / Footer', 'loom' ),
			'header'            => __( 'Header', 'loom' ),
			'footer'            => __( 'Footer', 'loom' ),
			'newHeader'         => __( 'New header', 'loom' ),
			'newFooter'         => __( 'New footer', 'loom' ),
			'noTemplates'       => __( 'None yet.', 'loom' ),
			'allTemplates'      => __( 'All templates', 'loom' ),
			'draft'             => __( 'Draft', 'loom' ),
			'moreTools'         => __( 'More tools', 'loom' ),
		),
	);

	/**
	 * Filter the editor configuration supplied to Loom add-ons.
	 *
	 * @param array $config  Editor configuration.
	 * @param int   $post_id Edited post ID.
	 */
	return (array) apply_filters( 'loom_editor_config', $config, (int) $post_id );
}
