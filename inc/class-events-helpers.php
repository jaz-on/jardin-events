<?php
/**
 * Role helpers and counts for events.
 *
 * @package Jardin_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default role slugs (closed enum).
 *
 * @return string[]
 */
function jardin_events_get_role_slugs() {
	$roles = array( 'speaker', 'organizer', 'sponsor', 'attendee' );
	return apply_filters( 'jardin_events_roles', $roles );
}

/**
 * Human-readable labels for roles (i18n).
 *
 * @return array<string,string> slug => label
 */
function jardin_events_get_role_labels() {
	$labels = array(
		'speaker'   => __( 'Speaker', 'jardin-events' ),
		'organizer' => __( 'Organizer', 'jardin-events' ),
		'sponsor'   => __( 'Sponsor', 'jardin-events' ),
		'attendee'  => __( 'Attendee', 'jardin-events' ),
	);
	return apply_filters( 'jardin_events_role_labels', $labels );
}

/**
 * Sanitize a single event_role meta value.
 *
 * @param mixed $value Raw value.
 * @return string Empty if invalid.
 */
function jardin_events_sanitize_event_role_meta( $value ) {
	$slug = sanitize_key( is_string( $value ) ? $value : (string) $value );
	return in_array( $slug, jardin_events_get_role_slugs(), true ) ? $slug : '';
}

/**
 * All role values stored for an event.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function jardin_events_get_event_roles( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return array();
	}
	$raw = get_post_meta( $post_id, 'event_role', false );
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$out = array();
	foreach ( $raw as $v ) {
		$s = jardin_events_sanitize_event_role_meta( $v );
		if ( '' !== $s && ! in_array( $s, $out, true ) ) {
			$out[] = $s;
		}
	}
	return $out;
}

/**
 * Whether the event has a given role.
 *
 * @param int    $post_id Post ID.
 * @param string $role    Role slug.
 * @return bool
 */
function jardin_events_has_role( $post_id, $role ) {
	$role = sanitize_key( (string) $role );
	return '' !== $role && in_array( $role, jardin_events_get_event_roles( $post_id ), true );
}

/**
 * Count published events per role (one query per role; cached-friendly).
 *
 * @return array<string,int>
 */
function jardin_events_get_role_counts() {
	$slugs  = jardin_events_get_role_slugs();
	$counts = array_fill_keys( $slugs, 0 );

	foreach ( $slugs as $slug ) {
		$q = new WP_Query(
			apply_filters(
				'jardin_events_role_query_args',
				array(
					'post_type'      => 'event',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
					'meta_query'     => array(
						array(
							'key'   => 'event_role',
							'value' => $slug,
						),
					),
				),
				$slug
			)
		);
		$counts[ $slug ] = (int) $q->found_posts;
		wp_reset_postdata();
	}

	return apply_filters( 'jardin_events_role_filter_counts', $counts );
}

/**
 * Whether the event is still upcoming or ongoing (same logic as core meta query).
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function jardin_events_is_upcoming( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}
	$start = (string) get_post_meta( $post_id, 'event_date', true );
	if ( '' === $start ) {
		return false;
	}
	$today = Jardin_Events_Core::get_today_ymd();
	if ( strcmp( $start, $today ) >= 0 ) {
		return true;
	}
	$end = (string) get_post_meta( $post_id, 'event_end_date', true );
	if ( '' !== $end && strcmp( $end, $today ) >= 0 ) {
		return true;
	}
	return false;
}

/**
 * Signed days until event start (negative if past start date).
 *
 * @param int $post_id Post ID.
 * @return int|null Null if no start date.
 */
function jardin_events_days_until( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return null;
	}
	$start = (string) get_post_meta( $post_id, 'event_date', true );
	if ( '' === $start || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ) {
		return null;
	}
	$tz       = wp_timezone();
	$today    = new \DateTimeImmutable( 'now', $tz );
	$start_dt = \DateTimeImmutable::createFromFormat( 'Y-m-d', $start, $tz );
	if ( ! $start_dt ) {
		return null;
	}
	$t0 = (int) $today->format( 'U' );
	$t1 = (int) $start_dt->setTime( 0, 0 )->format( 'U' );
	return (int) round( ( $t1 - $t0 ) / DAY_IN_SECONDS );
}
