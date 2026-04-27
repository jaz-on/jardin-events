<?php
/**
 * Server-rendered role filter chips.
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'jardin_events_get_role_counts' ) ) {
	return '';
}

$labels = jardin_events_get_role_labels();
$counts = jardin_events_get_role_counts();
$base   = get_post_type_archive_link( 'event' );
if ( ! $base ) {
	return '';
}

$current = isset( $_GET['event_role'] ) ? sanitize_key( wp_unslash( $_GET['event_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$parts = array();
$parts[] = sprintf(
	'<a class="jardin-events-filter__link%s" href="%s">%s</a>',
	'' === $current ? ' is-active' : '',
	esc_url( $base ),
	esc_html__( 'All', 'jardin-events' )
);

foreach ( jardin_events_get_role_slugs() as $slug ) {
	$url   = add_query_arg( 'event_role', $slug, $base );
	$label = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
	$count = isset( $counts[ $slug ] ) ? (int) $counts[ $slug ] : 0;
	$text  = sprintf(
		/* translators: 1: role label, 2: count */
		__( '%1$s (%2$d)', 'jardin-events' ),
		$label,
		$count
	);
	$parts[] = sprintf(
		'<a class="jardin-events-filter__link%s" href="%s">%s</a>',
		$current === $slug ? ' is-active' : '',
		esc_url( $url ),
		esc_html( $text )
	);
}

return '<nav class="jardin-events-filter" aria-label="' . esc_attr__( 'Filter events by role', 'jardin-events' ) . '"><p class="jardin-events-filter__inner">' . implode( '<span class="jardin-events-filter__sep" aria-hidden="true"> · </span>', $parts ) . '</p></nav>';
