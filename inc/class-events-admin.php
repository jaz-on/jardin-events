<?php
/**
 * Block editor UI for Jardin Events.
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
	 * Enqueue the native Gutenberg "Informations" panel for event metadata.
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
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-i18n' ),
			(string) filemtime( $script_path ),
			true
		);
	}
}
