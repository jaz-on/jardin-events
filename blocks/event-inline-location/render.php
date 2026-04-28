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

$loc = get_post_meta( $event_post_id, 'event_location', true );
$loc = is_string( $loc ) ? trim( $loc ) : '';
if ( '' === $loc ) {
	return '';
}

return sprintf(
	'<span class="entry-loc">%s</span>',
	esc_html( $loc )
);
