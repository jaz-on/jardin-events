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
			__( 'The start date is not a valid date.', 'jardin-events' )
		);
	}
	if ( null === $end ) {
		return new WP_Error(
			'jardin_events_invalid_end_date',
			__( 'The end date is not a valid date.', 'jardin-events' )
		);
	}

	if ( ! empty( $args['require_non_empty_start'] ) && '' === $start ) {
		return new WP_Error(
			'jardin_events_missing_start',
			__( 'The start date is required.', 'jardin-events' )
		);
	}

	if ( '' !== $start && '' !== $end && strcmp( $end, $start ) < 0 ) {
		return new WP_Error(
			'jardin_events_invalid_range',
			__( 'The end date must be the same as or after the start date.', 'jardin-events' )
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
	$stored_start = $post_id ? (string) get_post_meta( $post_id, '_jardin_events_date', true ) : '';
	$stored_end   = $post_id ? (string) get_post_meta( $post_id, '_jardin_events_date_end', true ) : '';

	$start = jardin_events_parse_ymd_meta( $stored_start );
	$end   = jardin_events_parse_ymd_meta( $stored_end );

	$meta = $request->get_param( 'meta' );
	if ( is_array( $meta ) ) {
		if ( array_key_exists( '_jardin_events_date', $meta ) ) {
			$start = jardin_events_parse_ymd_meta( $meta['_jardin_events_date'] );
		}
		if ( array_key_exists( '_jardin_events_date_end', $meta ) ) {
			$end = jardin_events_parse_ymd_meta( $meta['_jardin_events_date_end'] );
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
	$defaults = array_values(
		array_filter(
			get_post_types(
				array(
					'public'             => true,
					'publicly_queryable' => true,
				),
				'names'
			),
			static function ( $post_type ) {
				return ! in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true );
			}
		)
	);
	if ( empty( $defaults ) ) {
		$defaults = array( 'post' );
	}
	$types = (array) apply_filters( 'jardin_events_event_article_post_types', $defaults );
	$types = array_values( array_unique( array_map( 'sanitize_key', $types ) ) );
	return empty( $types ) ? $defaults : $types;
}

/**
 * Normalize recap related content IDs from scalar/array value.
 *
 * @param mixed $meta_value Raw meta value.
 * @return int[]
 */
function jardin_events_normalize_related_content_ids( $meta_value ) {
	$raw = array();
	if ( is_array( $meta_value ) ) {
		$raw = $meta_value;
	} elseif ( null !== $meta_value && '' !== $meta_value ) {
		$raw = array( $meta_value );
	}
	$ids = array();
	foreach ( $raw as $value ) {
		$id = absint( $value );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Sanitize recap related content IDs (must match allowed post types).
 *
 * @param mixed $meta_value Meta value.
 * @return int[] Empty when invalid.
 */
function jardin_events_sanitize_meta_event_article( $meta_value ) {
	$ids           = jardin_events_normalize_related_content_ids( $meta_value );
	$allowed       = jardin_events_get_event_article_post_types();
	$validated_ids = array();
	foreach ( $ids as $id ) {
		$post_type = get_post_type( $id );
		if ( is_string( $post_type ) && in_array( $post_type, $allowed, true ) ) {
			$validated_ids[] = $id;
		}
	}
	return array_values( array_unique( $validated_ids ) );
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
	$ids = jardin_events_sanitize_meta_event_article( $meta_value );
	return empty( $ids ) ? 0 : (int) $ids[0];
}

/**
 * Canonical list of registered meta keys (extend via filter).
 *
 * @return string[]
 */
function jardin_events_get_meta_key_list() {
	$keys = array(
		'_jardin_events_date',
		'_jardin_events_date_end',
		'_jardin_events_city',
		'_jardin_events_country',
		'_jardin_events_map_url',
		'_jardin_events_link',
		'_jardin_events_ticket_url',
		'_jardin_events_article',
		'_jardin_events_slides_url',
		'_jardin_events_video_url',
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
