<?php
/**
 * Inline event metadata for home IRL rows (.entry-meta-inline).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = 0;
if ( isset( $block ) && $block instanceof WP_Block ) {
	$event_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
} elseif ( isset( $block ) && is_array( $block ) && isset( $block['context'] ) && is_array( $block['context'] ) ) {
	$event_post_id = isset( $block['context']['postId'] ) ? (int) $block['context']['postId'] : 0;
}
if ( $event_post_id <= 0 ) {
	$event_post_id = (int) get_the_ID();
}
if ( $event_post_id <= 0 ) {
	$current_post = get_post();
	if ( $current_post instanceof WP_Post ) {
		$event_post_id = (int) $current_post->ID;
	}
}
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$start          = (string) get_post_meta( $event_post_id, 'event_date', true );
$end            = (string) jardin_events_get_event_date_end( $event_post_id );
$formatted_when = '';

if ( '' !== $start ) {
	$formatted_start = function_exists( 'jardin_events_format_ymd_for_display' ) ? jardin_events_format_ymd_for_display( $start ) : $start;
	$formatted_end   = ( '' !== $end && function_exists( 'jardin_events_format_ymd_for_display' ) ) ? jardin_events_format_ymd_for_display( $end ) : $end;
	if ( '' !== $formatted_end && $formatted_end !== $formatted_start ) {
		$formatted_when = sprintf( '%1$s - %2$s', $formatted_start, $formatted_end );
	} else {
		$formatted_when = $formatted_start;
	}
} else {
	$formatted_when = get_the_date( 'd/m/Y', $event_post_id );
}

$city     = trim( (string) get_post_meta( $event_post_id, 'event_city', true ) );
$country  = trim( (string) get_post_meta( $event_post_id, 'event_country', true ) );
$location = '';
if ( '' !== $city && '' !== $country ) {
	$location = $city . ', ' . $country;
} elseif ( '' !== $city ) {
	$location = $city;
} elseif ( '' !== $country ) {
	$location = $country;
}

if ( '' === $formatted_when && '' === $location ) {
	return '';
}

$parts = array();
if ( '' !== $formatted_when ) {
	$parts[] = sprintf( '<span class="entry-when">%s</span>', esc_html( $formatted_when ) );
}
if ( '' !== $formatted_when && '' !== $location ) {
	$parts[] = '<span class="entry-meta-sep" aria-hidden="true"> - </span>';
}
if ( '' !== $location ) {
	$parts[] = sprintf( '<span class="entry-loc">%s</span>', esc_html( $location ) );
}

return sprintf(
	'<span class="entry-meta-inline">%s</span>',
	implode( '', $parts ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe fragments escaped above.
);
