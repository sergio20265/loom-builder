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
			'layout'      => __( 'Layout', 'loom-builder' ),
			'basic'       => __( 'Basic', 'loom-builder' ),
			'media'       => __( 'Media', 'loom-builder' ),
			'site'        => __( 'Site', 'loom-builder' ),
			'woocommerce' => __( 'WooCommerce', 'loom-builder' ),
		),
		'i18n'        => array(
			'save'              => __( 'Save', 'loom-builder' ),
			'saved'             => __( 'Saved', 'loom-builder' ),
			'saving'            => __( 'Saving...', 'loom-builder' ),
			'exit'              => __( 'Exit', 'loom-builder' ),
			'addSection'        => __( 'Add Section', 'loom-builder' ),
			'content'           => __( 'Content', 'loom-builder' ),
			'style'             => __( 'Style', 'loom-builder' ),
			'advanced'          => __( 'Advanced', 'loom-builder' ),
			'widgets'           => __( 'Widgets', 'loom-builder' ),
			'structure'         => __( 'Structure', 'loom-builder' ),
			'expand'            => __( 'Expand', 'loom-builder' ),
			'collapse'          => __( 'Collapse', 'loom-builder' ),
			'search'            => __( 'Search widget...', 'loom-builder' ),
			'empty'             => __( 'Drag a widget here', 'loom-builder' ),
			'delete'            => __( 'Delete', 'loom-builder' ),
			'duplicate'         => __( 'Duplicate', 'loom-builder' ),
			'desktop'           => __( 'Desktop', 'loom-builder' ),
			'tablet'            => __( 'Tablet', 'loom-builder' ),
			'mobile'            => __( 'Mobile', 'loom-builder' ),
			'selectMedia'       => __( 'Select image', 'loom-builder' ),
			// Inspector / canvas chrome.
			'section'           => __( 'Section', 'loom-builder' ),
			'column'            => __( 'Column', 'loom-builder' ),
			'row'               => __( 'Row', 'loom-builder' ),
			'selectElement'     => __( 'Select an element to edit it.', 'loom-builder' ),
			'adjustLayout'      => __( 'Adjust layout in the Style tab.', 'loom-builder' ),
			'pageEmpty'         => __( 'Your page is empty.', 'loom-builder' ),
			'dragMove'          => __( 'Drag to move', 'loom-builder' ),
			'exportSection'     => __( 'Export section', 'loom-builder' ),
			'undo'              => __( 'Undo', 'loom-builder' ),
			'redo'              => __( 'Redo', 'loom-builder' ),
			'importSections'    => __( 'Import sections', 'loom-builder' ),
			'exportPage'        => __( 'Export page', 'loom-builder' ),
			'invalidFile'       => __( 'Invalid layout file.', 'loom-builder' ),
			'unsaved'          => __( 'Unsaved', 'loom-builder' ),
			'unsavedChanges'   => __( 'You have unsaved changes. Leave without saving?', 'loom-builder' ),
			'saveError'        => __( 'Could not save. Please try again.', 'loom-builder' ),
			'error'            => __( 'Error', 'loom-builder' ),
			'moveUp'            => __( 'Up', 'loom-builder' ),
			'moveDown'          => __( 'Down', 'loom-builder' ),
			'dynamicField'      => __( 'Dynamic field', 'loom-builder' ),
			'staticValue'       => __( '— Static —', 'loom-builder' ),
			// Style panel.
			'padding'           => __( 'Padding', 'loom-builder' ),
			'margin'            => __( 'Margin', 'loom-builder' ),
			'background'        => __( 'Background', 'loom-builder' ),
			'backgroundImage'   => __( 'Background image', 'loom-builder' ),
			'textAlign'         => __( 'Text align', 'loom-builder' ),
			'left'              => __( 'Left', 'loom-builder' ),
			'center'            => __( 'Center', 'loom-builder' ),
			'right'             => __( 'Right', 'loom-builder' ),
			'textColor'         => __( 'Text color', 'loom-builder' ),
			'fontSize'          => __( 'Font size', 'loom-builder' ),
			'fontWeight'        => __( 'Font weight', 'loom-builder' ),
			'minHeight'         => __( 'Min height', 'loom-builder' ),
			'radius'            => __( 'Radius', 'loom-builder' ),
			'maxWidth'          => __( 'Max width', 'loom-builder' ),
			'contentWidth'      => __( 'Content width', 'loom-builder' ),
			'contentWidthDefault' => __( 'Site default', 'loom-builder' ),
			'contentWidthPx'    => __( 'Custom (px)', 'loom-builder' ),
			'contentWidthPct'   => __( 'Custom (%) / full width', 'loom-builder' ),
			'columnWidth'       => __( 'Column width % (0 = auto)', 'loom-builder' ),
			'columnBasis'       => __( 'Column basis', 'loom-builder' ),
			'flexGrow'          => __( 'Flex grow', 'loom-builder' ),
			'flexShrink'        => __( 'Flex shrink', 'loom-builder' ),
			'unitPx'            => __( 'px', 'loom-builder' ),
			'unitPercent'       => __( '%', 'loom-builder' ),
			'unitVw'            => __( 'vw', 'loom-builder' ),
			'unitVh'            => __( 'vh', 'loom-builder' ),
			'unitAuto'          => __( 'Auto', 'loom-builder' ),
			'clear'             => __( 'Clear', 'loom-builder' ),
			'bgSize'            => __( 'Background size', 'loom-builder' ),
			'bgSizeCover'       => __( 'Cover', 'loom-builder' ),
			'bgSizeContain'     => __( 'Contain', 'loom-builder' ),
			'bgSizeAuto'        => __( 'Auto', 'loom-builder' ),
			'contentJustify'    => __( 'Content vertical alignment', 'loom-builder' ),
			'contentValign'     => __( 'Content horizontal alignment', 'loom-builder' ),
			'columnsGap'        => __( 'Columns gap', 'loom-builder' ),
			'columnsLayout'     => __( 'Columns layout', 'loom-builder' ),
			'columnsRow'        => __( 'Row', 'loom-builder' ),
			'columnsStack'      => __( 'Stack', 'loom-builder' ),
			'columnsHorizontal' => __( 'Columns horizontal alignment', 'loom-builder' ),
			'columnsVertical'   => __( 'Columns vertical alignment', 'loom-builder' ),
			'horizontalAlign'   => __( 'Horizontal alignment', 'loom-builder' ),
			'verticalAlign'     => __( 'Vertical alignment', 'loom-builder' ),
			'alignStart'        => __( 'Start', 'loom-builder' ),
			'alignEnd'          => __( 'End', 'loom-builder' ),
			'alignSpaceBetween' => __( 'Space between', 'loom-builder' ),
			'alignStretch'      => __( 'Stretch', 'loom-builder' ),
			'alignTop'          => __( 'Top', 'loom-builder' ),
			'alignBottom'       => __( 'Bottom', 'loom-builder' ),
			// Advanced panel.
			'none'              => __( 'None', 'loom-builder' ),
			'cssId'             => __( 'CSS ID', 'loom-builder' ),
			'cssClasses'        => __( 'CSS Classes', 'loom-builder' ),
			'entranceAnimation' => __( 'Entrance animation', 'loom-builder' ),
			'duration'          => __( 'Duration (ms)', 'loom-builder' ),
			'delay'             => __( 'Delay (ms)', 'loom-builder' ),
			'easing'            => __( 'Easing', 'loom-builder' ),
			'loopAnimation'     => __( 'Loop animation', 'loom-builder' ),
			'hoverAnimation'    => __( 'Hover animation', 'loom-builder' ),
			'hideDesktop'       => __( 'Hide on desktop', 'loom-builder' ),
			'hideTablet'        => __( 'Hide on tablet', 'loom-builder' ),
			'hideMobile'        => __( 'Hide on mobile', 'loom-builder' ),
			// Topbar: header/footer quick access and the "More tools" overflow menu.
			'headerFooter'      => __( 'Header / Footer', 'loom-builder' ),
			'header'            => __( 'Header', 'loom-builder' ),
			'footer'            => __( 'Footer', 'loom-builder' ),
			'newHeader'         => __( 'New header', 'loom-builder' ),
			'newFooter'         => __( 'New footer', 'loom-builder' ),
			'noTemplates'       => __( 'None yet.', 'loom-builder' ),
			'allTemplates'      => __( 'All templates', 'loom-builder' ),
			'draft'             => __( 'Draft', 'loom-builder' ),
			'moreTools'         => __( 'More tools', 'loom-builder' ),
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
