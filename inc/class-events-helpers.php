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
 * Registered event CPT slug (filterable).
 *
 * @return string
 */
function jardin_events_get_post_type() {
	return apply_filters( 'jardin_events_post_type', 'event' );
}

/**
 * Default rewrite slug for the event archive/single prefix (filterable).
 *
 * @return string
 */
function jardin_events_get_rewrite_slug() {
	return apply_filters( 'jardin_events_slug', 'evenements' );
}

/**
 * Event role taxonomy slug (filterable).
 *
 * @return string
 */
function jardin_events_get_role_taxonomy() {
	return apply_filters( 'jardin_events_role_taxonomy', 'event_role' );
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
		'organizer' => __( 'Organisateur·rice', 'jardin-events' ),
		'sponsor'   => __( 'Sponsor', 'jardin-events' ),
		'attendee'  => __( 'Participant·e', 'jardin-events' ),
	);
	return apply_filters( 'jardin_events_role_labels', $labels );
}

/**
 * Sanitize a single event role slug.
 *
 * @param mixed $value Raw value.
 * @return string Empty if invalid.
 */
function jardin_events_sanitize_event_role_slug( $value ) {
	$slug = sanitize_key( is_string( $value ) ? $value : (string) $value );
	return in_array( $slug, jardin_events_get_role_slugs(), true ) ? $slug : '';
}

/**
 * Backward-compatible alias.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function jardin_events_sanitize_event_role_meta( $value ) {
	return jardin_events_sanitize_event_role_slug( $value );
}

/**
 * All role terms assigned to an event.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function jardin_events_get_event_roles( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return array();
	}
	$terms = wp_get_post_terms(
		$post_id,
		jardin_events_get_role_taxonomy(),
		array(
			'fields' => 'slugs',
		)
	);
	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}
	$out = array();
	foreach ( $terms as $v ) {
		$s = jardin_events_sanitize_event_role_slug( $v );
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

	$terms = get_terms(
		array(
			'taxonomy'   => jardin_events_get_role_taxonomy(),
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return apply_filters( 'jardin_events_role_filter_counts', $counts );
	}
	foreach ( $terms as $term ) {
		if ( isset( $counts[ $term->slug ] ) ) {
			$counts[ $term->slug ] = (int) $term->count;
		}
	}

	return apply_filters( 'jardin_events_role_filter_counts', $counts );
}

/**
 * Filters payload for archive UI: role slugs, labels, counts, published total.
 *
 * @return array{roles: string[], labels: array<string,string>, counts: array<string,int>, total: int}
 */
function jardin_events_get_filters() {
	$pt        = jardin_events_get_post_type();
	$counters  = wp_count_posts( $pt );
	$published = isset( $counters->publish ) ? (int) $counters->publish : 0;

	$data = array(
		'roles'  => jardin_events_get_role_slugs(),
		'labels' => jardin_events_get_role_labels(),
		'counts' => jardin_events_get_role_counts(),
		'total'  => $published,
	);

	return apply_filters( 'jardin_events_filters', $data );
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
	$end = (string) jardin_events_get_event_date_end( $post_id );
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

/**
 * Primary role slug for markup (first stored role), or empty string.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function jardin_events_get_primary_role_slug( $post_id ) {
	$roles = jardin_events_get_event_roles( $post_id );
	return isset( $roles[0] ) ? (string) $roles[0] : '';
}

/**
 * Short status label for badges (à venir / en cours / passé).
 *
 * @param int $post_id Event post ID.
 * @return string
 */
function jardin_events_status_label( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return '';
	}
	if ( ! jardin_events_is_upcoming( $post_id ) ) {
		return __( 'Passé', 'jardin-events' );
	}
	$start = (string) get_post_meta( $post_id, 'event_date', true );
	$today = Jardin_Events_Core::get_today_ymd();
	if ( '' !== $start && strcmp( $start, $today ) > 0 ) {
		return __( 'À venir', 'jardin-events' );
	}
	return __( 'En cours', 'jardin-events' );
}

/**
 * Human-readable countdown / offset from today (start date).
 *
 * @param int $post_id Event post ID.
 * @return string Empty when no start date.
 */
function jardin_events_countdown_text( $post_id ) {
	$d = jardin_events_days_until( $post_id );
	if ( null === $d ) {
		return '';
	}
	if ( $d > 1 ) {
		return sprintf(
			/* translators: %d: full days until start */
			__( 'dans %d jours', 'jardin-events' ),
			$d
		);
	}
	if ( 1 === $d ) {
		return __( 'demain', 'jardin-events' );
	}
	if ( 0 === $d ) {
		return __( 'aujourd’hui', 'jardin-events' );
	}
	return sprintf(
		/* translators: %d: full days since start */
		__( 'il y a %d jours', 'jardin-events' ),
		abs( $d )
	);
}

/**
 * End date meta value (canonical key event_date_end; legacy event_end_date ignored after migration).
 *
 * @param int $post_id Event post ID.
 * @return string Y-m-d or empty.
 */
function jardin_events_get_event_date_end( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return '';
	}
	$v = get_post_meta( $post_id, 'event_date_end', true );
	return is_string( $v ) ? $v : '';
}

/**
 * Build a human-friendly event location from city/country.
 *
 * @param int $post_id Event post ID.
 * @return string
 */
function jardin_events_get_event_location_label( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return '';
	}
	$city    = get_post_meta( $post_id, 'event_city', true );
	$country = get_post_meta( $post_id, 'event_country', true );
	$city    = is_string( $city ) ? trim( $city ) : '';
	$country = is_string( $country ) ? trim( $country ) : '';
	if ( '' !== $city && '' !== $country ) {
		return $city . ' · ' . $country;
	}
	if ( '' !== $city ) {
		return $city;
	}
	return $country;
}

/**
 * Related content IDs from event meta (event_article).
 *
 * @param int $post_id Event post ID.
 * @return int[]
 */
function jardin_events_get_event_related_content_ids( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return array();
	}
	$raw = get_post_meta( $post_id, 'event_article', true );
	if ( is_array( $raw ) ) {
		$ids = array_map( 'absint', $raw );
		$ids = array_values( array_filter( $ids ) );
		return array_values( array_unique( $ids ) );
	}
	$single = absint( $raw );
	return $single > 0 ? array( $single ) : array();
}

/**
 * First related content post ID from event meta (event_article).
 *
 * @param int $post_id Event post ID.
 * @return int
 */
function jardin_events_get_event_article_id( $post_id ) {
	$ids = jardin_events_get_event_related_content_ids( $post_id );
	return empty( $ids ) ? 0 : (int) $ids[0];
}

/**
 * Linked recap article post ID from event meta (alias).
 *
 * @param int $post_id Event post ID.
 * @return int
 */
function jardin_events_get_linked_post_id( $post_id ) {
	return jardin_events_get_event_article_id( $post_id );
}

/**
 * Linked recap article post object if valid.
 *
 * @param int $post_id Event post ID.
 * @return \WP_Post|null
 */
function jardin_events_get_linked_post( $post_id ) {
	$lid = jardin_events_get_event_article_id( $post_id );
	if ( $lid <= 0 ) {
		return null;
	}
	$p = get_post( $lid );
	return ( $p instanceof \WP_Post ) ? $p : null;
}

/**
 * Find a published event that links to this article as recap (event_article).
 *
 * @param int $post_id Article post ID.
 * @return \WP_Post|null
 */
function jardin_events_find_event_for_recap_post( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return null;
	}
	$q = new \WP_Query(
		array(
			'post_type'              => jardin_events_get_post_type(),
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				'relation' => 'OR',
				array(
					'key'     => 'event_article',
					'value'   => $post_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => 'event_article',
					'value'   => 'i:' . $post_id . ';',
					'compare' => 'LIKE',
				),
			),
		)
	);
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return null;
	}
	$found = $q->posts[0];
	wp_reset_postdata();
	return $found instanceof \WP_Post ? $found : null;
}

/**
 * HTML for archive row role pills (spans.entry-role).
 *
 * @param int $post_id Event post ID.
 * @return string
 */
function jardin_events_get_role_pills_html( $post_id ) {
	$post_id = (int) $post_id;
	$roles   = jardin_events_get_event_roles( $post_id );
	$labels  = jardin_events_get_role_labels();
	$base    = get_post_type_archive_link( jardin_events_get_post_type() );
	$parts   = array();
	foreach ( $roles as $slug ) {
		$url   = $base ? add_query_arg( 'event_role', $slug, $base ) : '';
		$label = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
		if ( $url ) {
			$parts[] = sprintf(
				'<a class="entry-role entry-role--%1$s" href="%2$s">%3$s</a>',
				esc_attr( $slug ),
				esc_url( $url ),
				esc_html( $label )
			);
		} else {
			$parts[] = sprintf(
				'<span class="entry-role entry-role--%1$s">%2$s</span>',
				esc_attr( $slug ),
				esc_html( $label )
			);
		}
	}
	return implode( '', $parts );
}

/**
 * HTML for single inline role pills (links to filtered archive).
 *
 * @param int $post_id Event post ID.
 * @return string
 */
function jardin_events_get_role_pills_inline_html( $post_id ) {
	$post_id = (int) $post_id;
	$roles   = jardin_events_get_event_roles( $post_id );
	$labels  = jardin_events_get_role_labels();
	$base    = get_post_type_archive_link( jardin_events_get_post_type() );
	if ( empty( $roles ) ) {
		return '';
	}
	$parts = array();
	foreach ( $roles as $slug ) {
		$url   = $base ? add_query_arg( 'event_role', $slug, $base ) : '';
		$label = isset( $labels[ $slug ] ) ? $labels[ $slug ] : $slug;
		if ( $url ) {
			$parts[] = sprintf(
				'<a class="event-role-pill %1$s" href="%2$s">%3$s</a>',
				esc_attr( $slug ),
				esc_url( $url ),
				esc_html( $label )
			);
		} else {
			$parts[] = sprintf(
				'<span class="event-role-pill %1$s">%2$s</span>',
				esc_attr( $slug ),
				esc_html( $label )
			);
		}
	}
	return implode( ' ', $parts );
}
