<?php
/**
 * Plugin Name: Jardin Events
 * Description: Système d'événements (CPT event) pour le site Jardin, avec métadonnées et affichage de base.
 * Author: Jason Rouet
 * Version: 0.1.0
 * Text Domain: jardin-events
 * GitHub Plugin URI: https://github.com/jaz-on/jardin-event
 * Primary Branch: dev
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'JARDIN_EVENTS_VERSION', '0.1.0' );
define( 'JARDIN_EVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JARDIN_EVENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload core classes.
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-core.php';
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-admin.php';

/**
 * Initialize plugin.
 */
function jardin_events_init() {
	// Core logic (CPT, meta, queries).
	new Jardin_Events_Core();

	// Admin UI (meta boxes, settings).
	if ( is_admin() ) {
		new Jardin_Events_Admin();
	}
}
add_action( 'plugins_loaded', 'jardin_events_init' );

/**
 * Enqueue base styles on the front-end.
 */
function jardin_events_enqueue_styles() {
	$css_file = JARDIN_EVENTS_PLUGIN_DIR . 'assets/css/events-base.css';

	if ( file_exists( $css_file ) ) {
		wp_enqueue_style(
			'jardin-events-base',
			JARDIN_EVENTS_PLUGIN_URL . 'assets/css/events-base.css',
			array(),
			filemtime( $css_file )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'jardin_events_enqueue_styles' );


