<?php
/**
 * Inline location (.entry-loc) for home upcoming block.
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve current event ID from block context (Query Loop) with safe fallbacks.
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

$loc = function_exists( 'jardin_events_get_event_location_label' ) ? jardin_events_get_event_location_label( $event_post_id ) : '';
$loc = is_string( $loc ) ? trim( $loc ) : '';
if ( '' === $loc ) {
	return '';
}

return sprintf(
	'<span class="entry-loc">%s</span>',
	esc_html( $loc )
);
