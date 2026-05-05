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

	$tax_query   = $query->get( 'tax_query' );
	$tax_query   = is_array( $tax_query ) ? $tax_query : array();
	$tax_query[] = array(
		'taxonomy' => jardin_events_get_role_taxonomy(),
		'field'    => 'slug',
		'terms'    => array( $role ),
	);
	$query->set( 'tax_query', $tax_query );
}

/**
 * Order event archives by event_date (upcoming first).
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

	$query->set( 'meta_key', '_jardin_events_date' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'meta_type', 'DATE' );
	$query->set( 'order', 'ASC' );
}
add_action( 'pre_get_posts', 'jardin_events_pre_get_posts_event_role', 10 );
add_action( 'pre_get_posts', 'jardin_events_pre_get_posts_event_archive_order', 5 );
