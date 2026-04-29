<?php
/**
 * Uninstall jardin-events (plugin removal from disk).
 *
 * Event posts and post meta remain in the database; only transient notices are cleared.
 *
 * @package Jardin_Events
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off uninstall cleanup.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_jardin_events_invalid_dates_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_jardin_events_invalid_dates_' ) . '%'
	)
);
