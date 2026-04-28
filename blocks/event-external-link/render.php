<?php
/**
 * External « En savoir plus » link (.entry-links).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = (int) get_the_ID();
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$url = get_post_meta( $event_post_id, 'event_link', true );
$url = is_string( $url ) ? trim( $url ) : '';
if ( '' === $url ) {
	return '';
}

$label = __( 'En savoir plus', 'jardin-events' );

return sprintf(
	'<div class="entry-links"><a href="%1$s" rel="noopener noreferrer">%2$s</a></div>',
	esc_url( $url ),
	esc_html( $label )
);
