<?php
/**
 * Core logic for Jardin Events.
 *
 * @package Jardin_Events
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main core class: CPT registration, meta, queries, Query Loop integration.
 */
class Jardin_Events_Core {

	/**
	 * CSS class on a core/query block: upcoming events (by event_date / event_end_date).
	 */
	const QUERY_CLASS_UPCOMING = 'jardin-events-query--upcoming';

	/**
	 * CSS class on a core/query block: past events.
	 */
	const QUERY_CLASS_PAST = 'jardin-events-query--past';

	/**
	 * Whether to register runtime hooks (skip on activation bootstrap).
	 *
	 * @var bool
	 */
	private $register_hooks = true;

	/**
	 * Constructor.
	 *
	 * @param bool $register_hooks When false, only direct method calls run (activation).
	 */
	public function __construct( $register_hooks = true ) {
		$this->register_hooks = $register_hooks;

		if ( ! $this->register_hooks ) {
			return;
		}

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'query_loop_block_query_vars', array( $this, 'filter_query_loop_block_query_vars' ), 10, 3 );
	}

	/**
	 * Plugin activation: register types/meta and flush rewrite rules.
	 */
	public static function activate() {
		$core = new self( false );
		$core->register_post_type();
		$core->register_meta();
		flush_rewrite_rules();
	}

	/**
	 * Today in site timezone as Y-m-d.
	 *
	 * @return string
	 */
	public static function get_today_ymd() {
		return current_time( 'Y-m-d' );
	}

	/**
	 * Meta query: events that are still upcoming or ongoing (per theme spec).
	 *
	 * @return array
	 */
	public static function build_upcoming_meta_query() {
		$today = self::get_today_ymd();

		return array(
			'relation' => 'OR',
			array(
				'key'     => 'event_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'relation' => 'AND',
				array(
					'key'     => 'event_end_date',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'event_end_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);
	}

	/**
	 * Meta query: events fully in the past (complement of upcoming).
	 *
	 * @return array
	 */
	public static function build_past_meta_query() {
		$today = self::get_today_ymd();

		return array(
			'relation' => 'AND',
			array(
				'key'     => 'event_date',
				'value'   => $today,
				'compare' => '<',
				'type'    => 'DATE',
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'event_end_date',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'event_end_date',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => 'event_end_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
		);
	}

	/**
	 * Whether query vars target only the event post type.
	 *
	 * @param array $query Query vars.
	 * @return bool
	 */
	private function query_is_event_only( $query ) {
		if ( empty( $query['post_type'] ) ) {
			return false;
		}

		$pt = $query['post_type'];

		if ( is_string( $pt ) ) {
			return 'event' === $pt;
		}

		if ( is_array( $pt ) ) {
			return array( 'event' ) === $pt || ( 1 === count( $pt ) && 'event' === $pt[0] );
		}

		return false;
	}

	/**
	 * Adjust core/query block when marked with plugin CSS classes.
	 *
	 * @param array     $query Query vars.
	 * @param \WP_Block $block Block instance.
	 * @param int       $page  Page number (unused).
	 * @return array
	 */
	public function filter_query_loop_block_query_vars( $query, $block, $page ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->query_is_event_only( $query ) ) {
			return $query;
		}

		$class = isset( $block->parsed_block['attrs']['className'] ) ? (string) $block->parsed_block['attrs']['className'] : '';

		$is_upcoming = strpos( $class, self::QUERY_CLASS_UPCOMING ) !== false;
		$is_past     = strpos( $class, self::QUERY_CLASS_PAST ) !== false;

		if ( ! $is_upcoming && ! $is_past ) {
			return $query;
		}

		$query['meta_key']  = 'event_date';
		$query['orderby']   = 'meta_value';
		$query['meta_type'] = 'DATE';

		if ( $is_upcoming ) {
			$query['order']      = 'ASC';
			$query['meta_query'] = self::build_upcoming_meta_query();
		} else {
			$query['order']      = 'DESC';
			$query['meta_query'] = self::build_past_meta_query();
		}

		return $query;
	}

	/**
	 * Register the `event` custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Événements', 'jardin-events' ),
			'singular_name'      => __( 'Événement', 'jardin-events' ),
			'add_new'            => __( 'Ajouter un événement', 'jardin-events' ),
			'add_new_item'       => __( 'Ajouter un nouvel événement', 'jardin-events' ),
			'edit_item'          => __( 'Modifier l\'événement', 'jardin-events' ),
			'new_item'           => __( 'Nouvel événement', 'jardin-events' ),
			'view_item'          => __( 'Voir l\'événement', 'jardin-events' ),
			'search_items'       => __( 'Rechercher des événements', 'jardin-events' ),
			'not_found'          => __( 'Aucun événement trouvé', 'jardin-events' ),
			'not_found_in_trash' => __( 'Aucun événement dans la corbeille', 'jardin-events' ),
			'all_items'          => __( 'Tous les événements', 'jardin-events' ),
			'archives'           => __( 'Archives des événements', 'jardin-events' ),
			'menu_name'          => __( 'Événements', 'jardin-events' ),
		);

		$args = array(
			'label'               => __( 'Événements', 'jardin-events' ),
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => true,
			'has_archive'         => true,
			'rewrite'             => array(
				'slug' => 'events',
			),
			'supports'            => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
			),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-calendar-alt',
			'menu_position'       => 6,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_in_admin_bar'   => true,
		);

		register_post_type( 'event', $args );
	}

	/**
	 * Register post meta for events.
	 */
	public function register_meta() {
		$meta_args = array(
			'show_in_rest'  => true,
			'single'        => true,
			'auth_callback' => array( $this, 'meta_auth_callback' ),
			'type'          => 'string',
		);

		register_post_meta( 'event', 'event_date', $meta_args );
		register_post_meta( 'event', 'event_end_date', $meta_args );
		register_post_meta( 'event', 'event_location', $meta_args );
		register_post_meta( 'event', 'event_link', $meta_args );
	}

	/**
	 * Authorization callback for meta.
	 *
	 * @param bool   $allowed  Whether the user can add the meta.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @param string $cap      Capability.
	 * @param array  $caps     All capabilities.
	 * @return bool
	 */
	public function meta_auth_callback( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get upcoming events.
	 *
	 * @param int $limit Number of events.
	 * @return WP_Query
	 */
	public function get_upcoming_events( $limit = 3 ) {
		$args = array(
			'post_type'      => 'event',
			'posts_per_page' => $limit,
			'meta_query'     => self::build_upcoming_meta_query(),
			'orderby'        => 'meta_value',
			'meta_key'       => 'event_date',
			'meta_type'      => 'DATE',
			'order'          => 'ASC',
		);

		return new WP_Query( $args );
	}

	/**
	 * Get past events.
	 *
	 * @param int $limit Number of events.
	 * @return WP_Query
	 */
	public function get_past_events( $limit = 10 ) {
		$args = array(
			'post_type'      => 'event',
			'posts_per_page' => $limit,
			'meta_query'     => self::build_past_meta_query(),
			'orderby'        => 'meta_value',
			'meta_key'       => 'event_date',
			'meta_type'      => 'DATE',
			'order'          => 'DESC',
		);

		return new WP_Query( $args );
	}

	/**
	 * Format event date (start/end).
	 *
	 * @param int $post_id Event post ID.
	 * @return string
	 */
	public static function format_event_date( $post_id ) {
		$start = get_post_meta( $post_id, 'event_date', true );
		$end   = get_post_meta( $post_id, 'event_end_date', true );

		if ( ! $start ) {
			return '';
		}

		$start_ts        = strtotime( $start );
		$formatted_start = date_i18n( get_option( 'date_format' ), $start_ts );

		if ( $end ) {
			$end_ts        = strtotime( $end );
			$formatted_end = date_i18n( get_option( 'date_format' ), $end_ts );

			if ( $formatted_end !== $formatted_start ) {
				return sprintf( '%1$s – %2$s', $formatted_start, $formatted_end );
			}
		}

		return $formatted_start;
	}
}

/**
 * Singleton accessor for runtime core instance.
 *
 * @return Jardin_Events_Core
 */
function jardin_events_core() {
	static $core = null;

	if ( null === $core ) {
		$core = new Jardin_Events_Core();
	}

	return $core;
}

/**
 * Public helper functions (API).
 */

/**
 * Check if Jardin Events is active.
 *
 * @return bool
 */
function jardin_events_is_active() {
	return post_type_exists( 'event' );
}

/**
 * Get upcoming events.
 *
 * @param int $limit Number of posts.
 * @return WP_Query
 */
function jardin_events_get_upcoming( $limit = 3 ) {
	return jardin_events_core()->get_upcoming_events( $limit );
}

/**
 * Get past events.
 *
 * @param int $limit Number of posts.
 * @return WP_Query
 */
function jardin_events_get_past( $limit = 10 ) {
	return jardin_events_core()->get_past_events( $limit );
}

/**
 * Format event date.
 *
 * @param int $event_id Event ID.
 * @return string
 */
function jardin_events_format_date( $event_id ) {
	return Jardin_Events_Core::format_event_date( $event_id );
}
