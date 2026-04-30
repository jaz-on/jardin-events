<?php
/**
 * Server-rendered role filter chips (mockup .feed-filters.events-filters).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$labels = function_exists( 'jardin_events_get_role_labels' )
	? jardin_events_get_role_labels()
	: array();
$counts = function_exists( 'jardin_events_get_role_counts' )
	? jardin_events_get_role_counts()
	: array();

$post_type = function_exists( 'jardin_events_get_post_type' ) ? jardin_events_get_post_type() : 'event';
$base      = get_post_type_archive_link( $post_type );
if ( ! $base ) {
	$base = home_url( '/evenements/' );
}

$current = isset( $_GET['event_role'] ) ? sanitize_key( wp_unslash( $_GET['event_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$published_total = 0;
if ( function_exists( 'jardin_events_get_filters' ) ) {
	$p_filters       = jardin_events_get_filters();
	$published_total = isset( $p_filters['total'] ) ? (int) $p_filters['total'] : 0;
}

$parts = array();

$parts[] = sprintf(
	'<a class="ff-btn%1$s" href="%2$s" data-type="all" aria-pressed="%5$s"%6$s>%3$s <span class="ff-count">%4$d</span></a>',
	'' === $current ? ' active' : '',
	esc_url( $base ),
	esc_html__( 'All', 'jardin-events' ),
	$published_total,
	'' === $current ? 'true' : 'false',
	'' === $current ? ' aria-current="page"' : ''
);

$role_slugs = function_exists( 'jardin_events_get_role_slugs' )
	? jardin_events_get_role_slugs()
	: array();

foreach ( $role_slugs as $slug ) {
	$url     = add_query_arg( 'event_role', $slug, $base );
	$label   = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
	$cnt     = isset( $counts[ $slug ] ) ? (int) $counts[ $slug ] : 0;
	$parts[] = sprintf(
		'<a class="ff-btn%1$s" href="%2$s" data-type="%3$s" aria-pressed="%6$s"%7$s>%4$s <span class="ff-count">%5$d</span></a>',
		$current === $slug ? ' active' : '',
		esc_url( $url ),
		esc_attr( $slug ),
		esc_html( $label ),
		$cnt,
		$current === $slug ? 'true' : 'false',
		$current === $slug ? ' aria-current="page"' : ''
	);
}

$inner = implode( '', $parts );

return sprintf(
	'<div class="feed-filters events-filters u-w-full" data-filter="%1$s" role="navigation" aria-label="%2$s">%3$s</div>',
	esc_attr( '' === $current ? 'all' : $current ),
	esc_attr__( 'Filter events by role', 'jardin-events' ),
	$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* above.
);
