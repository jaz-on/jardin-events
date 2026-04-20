<?php
/**
 * Optional Event schema.org JSON-LD (off unless filtered).
 *
 * @package Jardin_Events
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end JSON-LD for single events.
 */
class Jardin_Events_Schema {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'maybe_print_jsonld' ) );
	}

	/**
	 * Print minimal Event JSON-LD on singular events when enabled.
	 */
	public function maybe_print_jsonld() {
		if ( ! is_singular( 'event' ) ) {
			return;
		}

		if ( ! apply_filters( 'jardin_events_enable_jsonld', false ) ) {
			return;
		}

		$post_id = (int) get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$start = (string) get_post_meta( $post_id, 'event_date', true );
		$end   = (string) get_post_meta( $post_id, 'event_end_date', true );
		$loc   = (string) get_post_meta( $post_id, 'event_location', true );
		$name  = wp_strip_all_tags( get_the_title( $post_id ) );

		$data = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Event',
			'name'     => $name,
		);

		if ( '' !== $start ) {
			$data['startDate'] = $start;
		}

		if ( '' !== $end ) {
			$data['endDate'] = $end;
		}

		if ( '' !== $loc ) {
			$data['location'] = array(
				'@type' => 'Place',
				'name'  => $loc,
			);
		}

		/**
		 * Filter the structured data array before JSON encoding.
		 *
		 * @param array $data    Schema.org Event data.
		 * @param int   $post_id Event post ID.
		 */
		$data = apply_filters( 'jardin_events_jsonld_data', $data, $post_id );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
