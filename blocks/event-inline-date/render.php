<?php
/**
 * Inline formatted event start date (.entry-when).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve current event ID from block context (Query Loop) with safe fallbacks.
 *
 * In nested dynamic blocks, `get_the_ID()` may be unavailable during early render
 * passes; block context is the most reliable source in Query Loop templates.
 */
$event_post_id = 0;
if ( isset( $block ) && $block instanceof WP_Block ) {
	$event_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
}
if ( $event_post_id <= 0 ) {
	$event_post_id = (int) get_the_ID();
}
if ( $event_post_id <= 0 ) {
	$post = get_post();
	if ( $post instanceof WP_Post ) {
		$event_post_id = (int) $post->ID;
	}
}
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$start = (string) get_post_meta( $event_post_id, 'event_date', true );
$class     = 'entry-when';
$formatted = '';

if ( '' !== $start ) {
	$formatted = function_exists( 'jardin_events_format_ymd_for_display' ) ? jardin_events_format_ymd_for_display( $start ) : $start;
	$today     = class_exists( 'Jardin_Events_Core' ) ? Jardin_Events_Core::get_today_ymd() : gmdate( 'Y-m-d' );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) && strcmp( $start, $today ) >= 0 ) {
		$class .= ' is-upcoming';
	}
} else {
	$formatted = get_the_date( 'd/m/Y', $event_post_id );
	$class    .= ' is-fallback';
}

if ( '' === trim( (string) $formatted ) ) {
	return '';
}

return sprintf(
	'<span class="%1$s">%2$s</span>',
	esc_attr( $class ),
	esc_html( $formatted )
);
