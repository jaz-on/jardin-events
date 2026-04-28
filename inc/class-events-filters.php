<?php
/**
 * Front-end query filters (?event_role= on event archives).
 *
 * @package Jardin_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restrict main event archive query by event_role GET param.
 *
 * @param WP_Query $query Main query.
 */
function jardin_events_pre_get_posts_event_role( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive( jardin_events_get_post_type() ) ) {
		return;
	}

	$role = isset( $_GET['event_role'] ) ? sanitize_key( wp_unslash( $_GET['event_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' === $role || ! in_array( $role, jardin_events_get_role_slugs(), true ) ) {
		return;
	}

	$meta_query   = $query->get( 'meta_query' );
	$meta_query   = is_array( $meta_query ) ? $meta_query : array();
	$meta_query[] = array(
		'key'   => 'event_role',
		'value' => $role,
	);
	$query->set( 'meta_query', $meta_query );
}

/**
 * Order event archives by event_date (newest first).
 *
 * @param WP_Query $query Main query.
 */
function jardin_events_pre_get_posts_event_archive_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive( jardin_events_get_post_type() ) ) {
		return;
	}

	$query->set( 'meta_key', 'event_date' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'meta_type', 'DATE' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'jardin_events_pre_get_posts_event_role', 10 );
add_action( 'pre_get_posts', 'jardin_events_pre_get_posts_event_archive_order', 5 );
