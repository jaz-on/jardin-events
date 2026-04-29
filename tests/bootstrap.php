<?php
/**
 * PHPUnit bootstrap for jardin-events.
 *
 * Set WP_TESTS_DIR to your WordPress test library path (wordpress-develop/tests/phpunit
 * or similar). Example: export WP_TESTS_DIR=/path/to/wordpress/tests/phpunit
 *
 * @package Jardin_Events
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir || ! is_dir( $_tests_dir ) ) {
	echo 'Please set WP_TESTS_DIR to a WordPress PHPUnit library directory.' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin for tests.
 */
function jardin_events_tests_load_plugin() {
	require dirname( __DIR__ ) . '/jardin-events.php';
}

tests_add_filter( 'muplugins_loaded', 'jardin_events_tests_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
