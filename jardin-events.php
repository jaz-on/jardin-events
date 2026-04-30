<?php
/**
 * Plugin Name: jardin-events
 * Plugin URI: https://github.com/jaz-on/jardin-events
 * Description: Registers an event custom post type with metadata and front-end styles for Jardin-style sites.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Jason Rouet
 * Author URI: https://jasonrouet.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jardin-events
 * GitHub Plugin URI: https://github.com/jaz-on/jardin-events
 * Primary Branch: dev
 *
 * Keep plugin header Version: in sync with JARDIN_EVENTS_VERSION on each release.
 *
 * @package Jardin_Events
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'JARDIN_EVENTS_VERSION', '0.1.0' );
define( 'JARDIN_EVENTS_PLUGIN_FILE', __FILE__ );
define( 'JARDIN_EVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JARDIN_EVENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JARDIN_EVENTS_GITHUB_URL', 'https://github.com/jaz-on/jardin-events' );
define( 'JARDIN_EVENTS_KOFI_URL', 'https://ko-fi.com/jasonrouet' );

// Autoload.
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/event-meta-helpers.php';
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-helpers.php';
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-core.php';
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-admin.php';
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-schema.php';
require_once JARDIN_EVENTS_PLUGIN_DIR . 'inc/class-events-filters.php';

register_activation_hook( __FILE__, array( 'Jardin_Events_Core', 'activate' ) );

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

/**
 * Load translations.
 */
function jardin_events_load_textdomain() {
	load_plugin_textdomain(
		'jardin-events',
		false,
		dirname( plugin_basename( JARDIN_EVENTS_PLUGIN_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'jardin_events_load_textdomain' );

/**
 * Initialize plugin.
 */
function jardin_events_init() {
	Jardin_Events_Core::migrate_legacy_meta_keys();

	jardin_events_core();

	new Jardin_Events_Schema();

	if ( is_admin() ) {
		new Jardin_Events_Admin();
	}
}
add_action( 'plugins_loaded', 'jardin_events_init' );

/**
 * Register plugin list hooks (action row + meta row).
 */
function jardin_events_register_plugin_list_hooks() {
	add_filter( 'plugin_action_links_' . plugin_basename( JARDIN_EVENTS_PLUGIN_FILE ), 'jardin_events_plugin_action_links' );
	add_filter( 'plugin_row_meta', 'jardin_events_plugin_row_meta', 10, 2 );
}
add_action( 'admin_init', 'jardin_events_register_plugin_list_hooks' );

/**
 * Add Events list link to the plugin action row.
 *
 * @param array $links Existing action links.
 * @return array Modified action links.
 */
function jardin_events_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'edit.php?post_type=event' ) ),
		esc_html__( 'Events', 'jardin-events' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Add GitHub and Donate links to the plugin meta row.
 *
 * @param array  $plugin_meta An array of plugin row meta links.
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @return array Plugin row meta links.
 */
function jardin_events_plugin_row_meta( $plugin_meta, $plugin_file ) {
	if ( plugin_basename( JARDIN_EVENTS_PLUGIN_FILE ) !== $plugin_file ) {
		return $plugin_meta;
	}

	$new_links = array(
		sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( JARDIN_EVENTS_GITHUB_URL ),
			esc_html__( 'GitHub', 'jardin-events' )
		),
		sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( JARDIN_EVENTS_KOFI_URL ),
			esc_html__( 'Donate', 'jardin-events' )
		),
	);

	return array_merge( $plugin_meta, $new_links );
}

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

/**
 * Register dynamic blocks (server-rendered).
 */
function jardin_events_register_blocks() {
	$dir         = JARDIN_EVENTS_PLUGIN_DIR . 'blocks/';
	$to_register = array(
		'event-filter',
		'event-status-bar',
		'event-archive-meta',
		'event-single-meta',
		'event-inline-meta',
		'event-inline-date',
		'event-external-link',
		'event-inline-location',
	);

	foreach ( $to_register as $slug ) {
		$path = $dir . $slug;
		if ( is_readable( $path . '/block.json' ) ) {
			/*
			 * Core's default loader for block.json "render" files does:
			 * ob_start(); require $file; return ob_get_clean();
			 * Templates that end with `return $html` therefore output nothing (return value is discarded).
			 * Our render/*.php files use `return`; capture require() output and merge with the buffer.
			 */
			$render_file = $path . '/render.php';
			$args        = array();
			if ( is_readable( $render_file ) ) {
				$args['render_callback'] = static function ( $attributes, $content, $block ) use ( $render_file ) {
					ob_start();
					// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Fixed path under plugin blocks/ + allowlisted slug.
					$included = require $render_file;
					$buffer   = ob_get_clean();
					if ( is_string( $included ) && '' !== $included ) {
						return $included;
					}
					return is_string( $buffer ) ? $buffer : '';
				};
			}
			register_block_type( $path, $args );
			continue;
		}

		$name   = 'jardin-events/' . $slug;
		$render = $path . '/render.php';

		register_block_type(
			$name,
			array(
				'render_callback' => static function () use ( $render ) {
					if ( ! is_readable( $render ) ) {
						return '';
					}

					$output = include $render;
					return is_string( $output ) ? $output : '';
				},
			)
		);
	}
}
add_action( 'init', 'jardin_events_register_blocks' );
