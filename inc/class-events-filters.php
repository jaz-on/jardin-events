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
	if ( ! $query->is_post_type_archive( 'event' ) ) {
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
add_action( 'pre_get_posts', 'jardin_events_pre_get_posts_event_role' );
