<?php
/**
 * Block binding sources for event CPT meta (Site Editor + core blocks).
 *
 * @package Jardin_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers {@see register_block_bindings_source()} entries when supported by Core.
 */
class Jardin_Events_Block_Bindings {

	/**
	 * Public source name: formatted event date (start / range).
	 */
	public const SOURCE_DATE = 'jardin-events/event-date-formatted';

	/**
	 * Public source name: city / country label.
	 */
	public const SOURCE_LOCATION = 'jardin-events/event-location';

	/**
	 * Public source name: role labels, plain text (middle dot separated).
	 */
	public const SOURCE_ROLES_PLAIN = 'jardin-events/event-roles-plain';

	/**
	 * Bootstrap on init (after CPT exists).
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::SOURCE_DATE,
			array(
				'label'              => __( 'Event — date (formatted)', 'jardin-events' ),
				'get_value_callback' => array( self::class, 'get_date_formatted' ),
				'uses_context'       => array( 'postId' ),
			)
		);

		register_block_bindings_source(
			self::SOURCE_LOCATION,
			array(
				'label'              => __( 'Event — location (city · country)', 'jardin-events' ),
				'get_value_callback' => array( self::class, 'get_location' ),
				'uses_context'       => array( 'postId' ),
			)
		);

		register_block_bindings_source(
			self::SOURCE_ROLES_PLAIN,
			array(
				'label'              => __( 'Event — roles (plain text)', 'jardin-events' ),
				'get_value_callback' => array( self::class, 'get_roles_plain' ),
				'uses_context'       => array( 'postId' ),
			)
		);
	}

	/**
	 * Resolve event post ID from block context or current post.
	 *
	 * @param \WP_Block $block_instance Block instance.
	 * @return int 0 if not an event.
	 */
	private static function resolve_event_post_id( $block_instance ) {
		$post_id = 0;
		if ( $block_instance instanceof WP_Block && ! empty( $block_instance->context['postId'] ) ) {
			$post_id = (int) $block_instance->context['postId'];
		}
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}
		if ( $post_id <= 0 ) {
			return 0;
		}
		if ( jardin_events_get_post_type() !== get_post_type( $post_id ) ) {
			return 0;
		}
		return $post_id;
	}

	/**
	 * Get formatted event date (start or start–end range) for block binding output.
	 *
	 * @param array     $source_args     Source args from block JSON.
	 * @param \WP_Block $block_instance  Block instance.
	 * @param string    $attribute_name  Bound attribute (e.g. content).
	 * @return string
	 */
	public static function get_date_formatted( array $source_args, WP_Block $block_instance, string $attribute_name ) {
		unset( $source_args, $attribute_name );
		$pid = self::resolve_event_post_id( $block_instance );
		if ( $pid <= 0 || ! class_exists( 'Jardin_Events_Core' ) ) {
			return '';
		}
		return (string) Jardin_Events_Core::format_event_date( $pid );
	}

	/**
	 * Get human-readable location (city · country) for block binding output.
	 *
	 * @param array     $source_args     Source args.
	 * @param \WP_Block $block_instance  Block instance.
	 * @param string    $attribute_name  Bound attribute.
	 * @return string
	 */
	public static function get_location( array $source_args, WP_Block $block_instance, string $attribute_name ) {
		unset( $source_args, $attribute_name );
		$pid = self::resolve_event_post_id( $block_instance );
		if ( $pid <= 0 || ! function_exists( 'jardin_events_get_event_location_label' ) ) {
			return '';
		}
		return (string) jardin_events_get_event_location_label( $pid );
	}

	/**
	 * Get role term names as plain text (middle dot separated) for block binding output.
	 *
	 * @param array     $source_args     Source args.
	 * @param \WP_Block $block_instance  Block instance.
	 * @param string    $attribute_name  Bound attribute.
	 * @return string
	 */
	public static function get_roles_plain( array $source_args, WP_Block $block_instance, string $attribute_name ) {
		unset( $source_args, $attribute_name );
		$pid = self::resolve_event_post_id( $block_instance );
		if ( $pid <= 0 ) {
			return '';
		}
		$slugs  = jardin_events_get_event_roles( $pid );
		$labels = jardin_events_get_role_labels();
		$parts  = array();
		foreach ( $slugs as $slug ) {
			if ( isset( $labels[ $slug ] ) && '' !== $labels[ $slug ] ) {
				$parts[] = $labels[ $slug ];
			}
		}
		return implode( ' · ', $parts );
	}
}

add_action( 'init', array( 'Jardin_Events_Block_Bindings', 'init' ), 20 );
