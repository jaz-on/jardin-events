<?php
/**
 * Inline formatted event start date (.entry-when).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = (int) get_the_ID();
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
