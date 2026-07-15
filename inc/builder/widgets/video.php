<?php
/**
 * Video widget. Embeds YouTube / Vimeo or a self-hosted file in a responsive,
 * aspect-ratio-locked frame.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		$registry->register(
			array(
				'id'       => 'video',
				'title'    => __( 'Video', 'loom-builder' ),
				'icon'     => 'video-alt3',
				'category' => 'media',
				'controls' => array(
					'source'   => array(
						'type'    => 'select',
						'label'   => __( 'Source', 'loom-builder' ),
						'default' => 'youtube',
						'options' => array(
							'youtube' => __( 'YouTube', 'loom-builder' ),
							'vimeo'   => __( 'Vimeo', 'loom-builder' ),
							'file'    => __( 'Self-hosted file', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'url'      => array(
						'type'    => 'text',
						'label'   => __( 'Video URL or ID', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
					'aspect'   => array(
						'type'    => 'select',
						'label'   => __( 'Aspect ratio', 'loom-builder' ),
						'default' => '16:9',
						'options' => array(
							'16:9' => '16:9',
							'4:3'  => '4:3',
							'21:9' => '21:9',
							'1:1'  => '1:1',
						),
						'section' => 'content',
					),
					'poster'   => array(
						'type'    => 'imageobj',
						'label'   => __( 'Poster image', 'loom-builder' ),
						'default' => array(),
						'section' => 'content',
					),
					'autoplay' => array( 'type' => 'toggle', 'label' => __( 'Autoplay', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
					'loop'     => array( 'type' => 'toggle', 'label' => __( 'Loop', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
					'muted'    => array( 'type' => 'toggle', 'label' => __( 'Muted', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
					'controls' => array( 'type' => 'toggle', 'label' => __( 'Controls', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
				),
				'render'   => 'loom_render_video',
			)
		);
	}
);

/**
 * Extract a YouTube video id from a URL or accept a bare id.
 *
 * @param string $url Raw input.
 * @return string
 */
function loom_video_youtube_id( $url ) {
	$url = trim( $url );
	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $url ) ) {
		return $url;
	}
	if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Extract a Vimeo video id from a URL or accept a bare id.
 *
 * @param string $url Raw input.
 * @return string
 */
function loom_video_vimeo_id( $url ) {
	$url = trim( $url );
	if ( preg_match( '/^\d+$/', $url ) ) {
		return $url;
	}
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Render the video widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_video( $s ) {
	$source = in_array( isset( $s['source'] ) ? $s['source'] : '', array( 'youtube', 'vimeo', 'file' ), true ) ? $s['source'] : 'youtube';
	$url    = isset( $s['url'] ) ? trim( (string) $s['url'] ) : '';

	$ratios = array(
		'16:9' => '16 / 9',
		'4:3'  => '4 / 3',
		'21:9' => '21 / 9',
		'1:1'  => '1 / 1',
	);
	$ratio = isset( $ratios[ $s['aspect'] ] ) ? $ratios[ $s['aspect'] ] : '16 / 9';

	$autoplay = ! empty( $s['autoplay'] );
	$loop     = ! empty( $s['loop'] );
	$muted    = ! empty( $s['muted'] );
	$controls = ! empty( $s['controls'] );

	$inner = '';

	if ( 'youtube' === $source ) {
		$id = loom_video_youtube_id( $url );
		if ( ! $id ) {
			return '<div class="loom-video-empty">' . esc_html__( 'Enter a valid YouTube URL or ID.', 'loom-builder' ) . '</div>';
		}
		$params = array(
			'rel'            => 0,
			'modestbranding' => 1,
		);
		if ( $autoplay ) {
			$params['autoplay'] = 1;
			$params['mute']     = 1;
		}
		if ( $muted ) {
			$params['mute'] = 1;
		}
		if ( $loop ) {
			$params['loop']     = 1;
			$params['playlist'] = $id;
		}
		if ( ! $controls ) {
			$params['controls'] = 0;
		}
		$src   = 'https://www.youtube-nocookie.com/embed/' . $id . '?' . http_build_query( $params );
		$inner = '<iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'Video', 'loom-builder' ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
	} elseif ( 'vimeo' === $source ) {
		$id = loom_video_vimeo_id( $url );
		if ( ! $id ) {
			return '<div class="loom-video-empty">' . esc_html__( 'Enter a valid Vimeo URL or ID.', 'loom-builder' ) . '</div>';
		}
		$params = array();
		if ( $autoplay ) {
			$params['autoplay'] = 1;
			$params['muted']    = 1;
		}
		if ( $muted ) {
			$params['muted'] = 1;
		}
		if ( $loop ) {
			$params['loop'] = 1;
		}
		$src   = 'https://player.vimeo.com/video/' . $id . ( $params ? '?' . http_build_query( $params ) : '' );
		$inner = '<iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'Video', 'loom-builder' ) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
	} else {
		if ( ! $url ) {
			return '<div class="loom-video-empty">' . esc_html__( 'Enter a video file URL.', 'loom-builder' ) . '</div>';
		}
		$attrs  = $controls ? ' controls' : '';
		$attrs .= $autoplay ? ' autoplay' : '';
		$attrs .= $loop ? ' loop' : '';
		$attrs .= ( $muted || $autoplay ) ? ' muted' : '';
		$poster = ! empty( $s['poster']['url'] ) ? ' poster="' . esc_url( $s['poster']['url'] ) . '"' : '';
		$inner  = '<video src="' . esc_url( $url ) . '"' . $attrs . $poster . ' playsinline preload="metadata"></video>';
	}

	return '<div class="loom-video" style="aspect-ratio:' . esc_attr( $ratio ) . '">' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
