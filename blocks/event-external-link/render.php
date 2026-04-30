<?php
/**
 * External event info / ticketing links (.entry-links).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = (int) get_the_ID();
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$event_url = get_post_meta( $event_post_id, 'event_link', true );
$event_url = is_string( $event_url ) ? trim( $event_url ) : '';
$ticket    = get_post_meta( $event_post_id, 'event_ticket_url', true );
$ticket    = is_string( $ticket ) ? trim( $ticket ) : '';

if ( '' === $event_url && '' === $ticket ) {
	$event_url = get_permalink( $event_post_id );
}

$links = array();
if ( '' !== $event_url ) {
	$links[] = sprintf(
		'<a href="%1$s" rel="noopener noreferrer">%2$s</a>',
		esc_url( $event_url ),
		esc_html__( 'Event page', 'jardin-events' )
	);
}

if ( '' !== $ticket ) {
	$links[] = sprintf(
		'<a href="%1$s" rel="noopener noreferrer">%2$s</a>',
		esc_url( $ticket ),
		esc_html__( 'Tickets', 'jardin-events' )
	);
}

return sprintf(
	'<div class="entry-links">%s</div>',
	implode( ' · ', $links )
);
