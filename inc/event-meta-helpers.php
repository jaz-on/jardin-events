<?php
/**
 * Event meta parsing, validation, and REST merge helpers.
 *
 * @package Jardin_Events
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse a stored or submitted date string to Y-m-d or empty, or null if invalid non-empty.
 *
 * @param mixed $value Raw value.
 * @return string|null|string Empty string, valid Y-m-d, or null when non-empty but invalid.
 */
function jardin_events_parse_ymd_meta( $value ) {
	if ( null === $value ) {
		return '';
	}
	if ( ! is_string( $value ) ) {
		return null;
	}
	$value = trim( $value );
	if ( '' === $value ) {
		return '';
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return null;
	}
	$parts = array_map( 'intval', explode( '-', $value, 3 ) );
	if ( 3 !== count( $parts ) ) {
		return null;
	}
	$y = $parts[0];
	$m = $parts[1];
	$d = $parts[2];
	return checkdate( $m, $d, $y ) ? $value : null;
}

/**
 * Validate event_date / event_date_end values after parsing.
 *
 * @param string|null $start Parsed start (Y-m-d, '', or null for invalid format of a non-empty raw).
 * @param string|null $end   Parsed end (same).
 * @param array       $args   Optional. `require_non_empty_start` (bool) when true (admin / REST create), empty start is invalid.
 * @return true|\WP_Error
 */
function jardin_events_validate_event_dates( $start, $end, $args = array() ) {
	if ( null === $start ) {
		return new WP_Error(
			'jardin_events_invalid_date',
			__( 'La date de début n’est pas une date valide.', 'jardin-events' )
		);
	}
	if ( null === $end ) {
		return new WP_Error(
			'jardin_events_invalid_end_date',
			__( 'La date de fin n’est pas une date valide.', 'jardin-events' )
		);
	}

	if ( ! empty( $args['require_non_empty_start'] ) && '' === $start ) {
		return new WP_Error(
			'jardin_events_missing_start',
			__( 'La date de début est obligatoire.', 'jardin-events' )
		);
	}

	if ( '' !== $start && '' !== $end && strcmp( $end, $start ) < 0 ) {
		return new WP_Error(
			'jardin_events_invalid_range',
			__( 'La date de fin doit être identique ou postérieure à la date de début.', 'jardin-events' )
		);
	}

	return true;
}

/**
 * Merge event dates from a REST request with stored meta (for partial updates).
 *
 * @param \WP_REST_Request $request Request object.
 * @param int              $post_id Post ID or 0 on create.
 * @return array{0: string|null, 1: string|null} Parsed start and end; null means invalid format for a provided raw value.
 */
function jardin_events_merge_event_dates_from_request( $request, $post_id ) {
	$stored_start = $post_id ? (string) get_post_meta( $post_id, 'event_date', true ) : '';
	$stored_end   = '';
	if ( $post_id ) {
		$stored_end = (string) get_post_meta( $post_id, 'event_date_end', true );
		if ( '' === $stored_end ) {
			$stored_end = (string) get_post_meta( $post_id, 'event_end_date', true );
		}
	}

	$start = jardin_events_parse_ymd_meta( $stored_start );
	$end   = jardin_events_parse_ymd_meta( $stored_end );

	$meta = $request->get_param( 'meta' );
	if ( is_array( $meta ) ) {
		if ( array_key_exists( 'event_date', $meta ) ) {
			$start = jardin_events_parse_ymd_meta( $meta['event_date'] );
		}
		if ( array_key_exists( 'event_date_end', $meta ) ) {
			$end = jardin_events_parse_ymd_meta( $meta['event_date_end'] );
		} elseif ( array_key_exists( 'event_end_date', $meta ) ) {
			$end = jardin_events_parse_ymd_meta( $meta['event_end_date'] );
		}
	}

	return array( $start, $end );
}

/**
 * Sanitize callback for date meta registration.
 *
 * @param mixed $meta_value Meta value.
 * @return string
 */
function jardin_events_sanitize_meta_event_date( $meta_value ) {
	$parsed = jardin_events_parse_ymd_meta( $meta_value );
	if ( null === $parsed ) {
		return '';
	}
	return $parsed;
}

/**
 * Sanitize callback for plain text meta.
 *
 * @param mixed $meta_value Meta value.
 * @return string
 */
function jardin_events_sanitize_meta_text( $meta_value ) {
	return sanitize_text_field( (string) $meta_value );
}

/**
 * Sanitize callback for URL meta.
 *
 * @param mixed $meta_value Meta value.
 * @return string
 */
function jardin_events_sanitize_meta_url( $meta_value ) {
	return esc_url_raw( (string) $meta_value );
}

/**
 * Allowed post types for `event_article` links.
 *
 * @return string[]
 */
function jardin_events_get_event_article_post_types() {
	$defaults = array( 'post' );
	$types    = (array) apply_filters( 'jardin_events_event_article_post_types', $defaults );
	$types    = array_values( array_unique( array_map( 'sanitize_key', $types ) ) );
	return empty( $types ) ? $defaults : $types;
}

/**
 * Sanitize recap article post ID (must match allowed post types).
 *
 * @param mixed $meta_value Meta value.
 * @return int Zero when empty or invalid.
 */
function jardin_events_sanitize_meta_event_article( $meta_value ) {
	$id = absint( $meta_value );
	if ( $id <= 0 ) {
		return 0;
	}
	$post_type = get_post_type( $id );
	if ( ! is_string( $post_type ) || ! in_array( $post_type, jardin_events_get_event_article_post_types(), true ) ) {
		return 0;
	}
	return $id;
}

/**
 * Back-compat sanitize alias for recap article meta.
 *
 * @deprecated Use {@see jardin_events_sanitize_meta_event_article()}
 *
 * @param mixed $meta_value Meta value.
 * @return int
 */
function jardin_events_sanitize_meta_linked_post( $meta_value ) {
	return jardin_events_sanitize_meta_event_article( $meta_value );
}

/**
 * Canonical list of registered meta keys (extend via filter).
 *
 * @return string[]
 */
function jardin_events_get_meta_key_list() {
	$keys = array(
		'event_date',
		'event_date_end',
		'event_location',
		'event_link',
		'event_ticket_url',
		'event_role',
		'event_article',
		'event_slides_url',
		'event_video_url',
	);
	return apply_filters( 'jardin_events_meta_keys', $keys );
}

/**
 * Convert a Y-m-d string to a Unix timestamp in the site timezone for formatting.
 *
 * @param string $ymd Date string Y-m-d.
 * @return int|false
 */
function jardin_events_ymd_to_timestamp( $ymd ) {
	if ( ! is_string( $ymd ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return false;
	}
	try {
		$tz = wp_timezone();
		$dt = new \DateTimeImmutable( $ymd . ' 12:00:00', $tz );
		return $dt->getTimestamp();
	} catch ( \Exception $e ) {
		return false;
	}
}

/**
 * Format a single Y-m-d value using the site's date format (for display).
 *
 * @param string $ymd Stored date Y-m-d.
 * @return string
 */
function jardin_events_format_ymd_for_display( $ymd ) {
	$ts = jardin_events_ymd_to_timestamp( $ymd );
	if ( false === $ts ) {
		return '';
	}
	return date_i18n( get_option( 'date_format' ), $ts );
}

/**
 * Today in the site timezone as Y-m-d (delegates to core; no behavior change).
 *
 * @return string
 */
function jardin_events_today_ymd() {
	return Jardin_Events_Core::get_today_ymd();
}
