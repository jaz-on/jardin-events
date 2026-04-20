<?php
/**
 * Bootstrap du plugin Jardin Events.
 *
 * @package Jardin_Events
 *
 * Plugin Name: Jardin Events
 * Description: Système d'événements (CPT event) pour le site Jardin, avec métadonnées et affichage de base.
 * Author: Jason Rouet
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

register_activation_hook( __FILE__, array( 'Jardin_Events_Core', 'activate' ) );

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

/**
 * Initialize plugin.
 */
function jardin_events_init() {
	jardin_events_core();

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
