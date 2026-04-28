<?php
/**
 * Server-rendered role filter chips (mockup .feed-filters.events-filters).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'jardin_events_get_role_counts' ) ) {
	return '';
}

$labels = jardin_events_get_role_labels();
$counts = jardin_events_get_role_counts();
$base   = get_post_type_archive_link( jardin_events_get_post_type() );
if ( ! $base ) {
	return '';
}

$current = isset( $_GET['event_role'] ) ? sanitize_key( wp_unslash( $_GET['event_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$p_filters       = jardin_events_get_filters();
$published_total = isset( $p_filters['total'] ) ? (int) $p_filters['total'] : 0;

$parts = array();

$parts[] = sprintf(
	'<a class="ff-btn%1$s" href="%2$s" data-type="all">%3$s <span class="ff-count">%4$d</span></a>',
	'' === $current ? ' active' : '',
	esc_url( $base ),
	esc_html__( 'tous', 'jardin-events' ),
	$published_total
);

foreach ( jardin_events_get_role_slugs() as $slug ) {
	$url     = add_query_arg( 'event_role', $slug, $base );
	$label   = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
	$cnt     = isset( $counts[ $slug ] ) ? (int) $counts[ $slug ] : 0;
	$parts[] = sprintf(
		'<a class="ff-btn%1$s" href="%2$s" data-type="%3$s">%4$s <span class="ff-count">%5$d</span></a>',
		$current === $slug ? ' active' : '',
		esc_url( $url ),
		esc_attr( $slug ),
		esc_html( $label ),
		$cnt
	);
}

$inner = implode( '', $parts );

return sprintf(
	'<div class="feed-filters events-filters u-w-full" data-filter="%1$s" role="navigation" aria-label="%2$s">%3$s</div>',
	esc_attr( '' === $current ? 'all' : $current ),
	esc_attr__( 'Filter events by role', 'jardin-events' ),
	$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* above.
);
