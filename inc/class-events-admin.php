<?php
/**
 * Block editor UI for jardin-events.
 *
 * @package Jardin_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jardin_Events_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_panel_assets' ) );
	}

	/**
	 * Enqueue the Gutenberg document panel for event metadata.
	 */
	public function enqueue_editor_panel_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || jardin_events_get_post_type() !== $screen->post_type ) {
			return;
		}

		$script_path = JARDIN_EVENTS_PLUGIN_DIR . 'assets/js/editor-event-info-panel.js';
		if ( ! is_readable( $script_path ) ) {
			return;
		}

		wp_enqueue_script(
			'jardin-events-editor-info-panel',
			JARDIN_EVENTS_PLUGIN_URL . 'assets/js/editor-event-info-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' ),
			(string) filemtime( $script_path ),
			true
		);

		wp_set_script_translations(
			'jardin-events-editor-info-panel',
			'jardin-events',
			JARDIN_EVENTS_PLUGIN_DIR . 'languages'
		);

		$style_path = JARDIN_EVENTS_PLUGIN_DIR . 'assets/css/editor-event-info-panel.css';
		if ( is_readable( $style_path ) ) {
			wp_enqueue_style(
				'jardin-events-editor-info-panel',
				JARDIN_EVENTS_PLUGIN_URL . 'assets/css/editor-event-info-panel.css',
				array(),
				(string) filemtime( $style_path )
			);
		}
	}
}
