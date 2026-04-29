<?php
/**
 * Inline location (.entry-loc) for home upcoming block.
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = (int) get_the_ID();
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
