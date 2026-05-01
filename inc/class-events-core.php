<?php
/**
 * Core logic for jardin-events.
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
	 * CSS class on a core/query block: upcoming events (by event_date / event_date_end).
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
		add_action( 'init', array( $this, 'register_role_taxonomy' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
		add_filter( 'query_loop_block_query_vars', array( $this, 'filter_query_loop_block_query_vars' ), 10, 3 );
		add_filter( 'rest_pre_insert_event', array( $this, 'rest_pre_insert_event' ), 10, 2 );
		add_filter( 'rest_pre_update_event', array( $this, 'rest_pre_update_event' ), 10, 3 );
	}

	/**
	 * Block REST create when merged event dates are invalid.
	 *
	 * @param array|\WP_Error  $prepared_post Post data.
	 * @param \WP_REST_Request $request      Request.
	 * @return array|\WP_Error
	 */
	public function rest_pre_insert_event( $prepared_post, $request ) {
		$meta            = $request->get_param( 'meta' );
		$has_start_in_request = is_array( $meta ) && array_key_exists( 'event_date', $meta );

		// In the block editor, our sidebar metabox fields can be posted outside REST `meta`.
		// If REST payload has no `event_date`, let classic metabox validation run on save_post.
		if ( ! $has_start_in_request ) {
			return $prepared_post;
		}

		list( $start, $end ) = jardin_events_merge_event_dates_from_request( $request, 0 );
		$check               = jardin_events_validate_event_dates(
			$start,
			$end,
			array( 'require_non_empty_start' => true )
		);

		return is_wp_error( $check ) ? $check : $prepared_post;
	}

	/**
	 * Block REST update when merged event dates are invalid.
	 *
	 * @param array|\WP_Error  $prepared_post Post data.
	 * @param \WP_Post         $post          Existing post.
	 * @param \WP_REST_Request $request      Request.
	 * @return array|\WP_Error
	 */
	public function rest_pre_update_event( $prepared_post, $post, $request ) {
		$post_id             = isset( $post->ID ) ? (int) $post->ID : 0;
		list( $start, $end ) = jardin_events_merge_event_dates_from_request( $request, $post_id );
		$check               = jardin_events_validate_event_dates(
			$start,
			$end,
			array( 'require_non_empty_start' => true )
		);

		return is_wp_error( $check ) ? $check : $prepared_post;
	}

	/**
	 * Plugin activation: register types/meta and flush rewrite rules.
	 */
	public static function activate() {
		self::migrate_legacy_meta_keys();
		$core = new self( false );
		$core->register_post_type();
		$core->register_role_taxonomy();
		$core->register_meta();
		flush_rewrite_rules();
	}

	/**
	 * Rename legacy meta keys in the database (one-time per site).
	 */
	public static function migrate_legacy_meta_keys() {
		if ( '2' === get_option( 'jardin_events_db_version', '' ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				'event_date_end',
				'event_end_date'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off migration.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				'event_article',
				'event_linked_post'
			)
		);

		update_option( 'jardin_events_db_version', '2' );
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
					'key'     => 'event_date_end',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'event_date_end',
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
					'key'     => 'event_date_end',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'event_date_end',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => 'event_date_end',
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

		$event_pt = jardin_events_get_post_type();

		if ( is_string( $pt ) ) {
			return $event_pt === $pt;
		}

		if ( is_array( $pt ) ) {
			return array( $event_pt ) === $pt || ( 1 === count( $pt ) && $event_pt === $pt[0] );
		}

		return false;
	}

	/**
	 * Current role filter from request when valid.
	 *
	 * @return string
	 */
	private function get_current_role_filter() {
		$role = isset( $_GET['event_role'] ) ? sanitize_key( wp_unslash( $_GET['event_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $role, jardin_events_get_role_slugs(), true ) ? $role : '';
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

		$role = $this->get_current_role_filter();
		if ( '' !== $role ) {
			$tax_query = isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ? $query['tax_query'] : array();
			$tax_query[] = array(
				'taxonomy' => jardin_events_get_role_taxonomy(),
				'field'    => 'slug',
				'terms'    => array( $role ),
			);
			$query['tax_query'] = $tax_query;
		}

		return apply_filters( 'jardin_events_query_loop_query_vars', $query, $block, $is_upcoming );
	}

	/**
	 * Register role taxonomy for events (multi-select).
	 */
	public function register_role_taxonomy() {
		$taxonomy = jardin_events_get_role_taxonomy();
		$post_type = jardin_events_get_post_type();

		$labels = array(
			'name'              => __( 'Roles', 'jardin-events' ),
			'singular_name'     => __( 'Role', 'jardin-events' ),
			'search_items'      => __( 'Search roles', 'jardin-events' ),
			'all_items'         => __( 'All roles', 'jardin-events' ),
			'edit_item'         => __( 'Edit role', 'jardin-events' ),
			'update_item'       => __( 'Update role', 'jardin-events' ),
			'add_new_item'      => __( 'Add role', 'jardin-events' ),
			'new_item_name'     => __( 'New role', 'jardin-events' ),
			'menu_name'         => __( 'Roles', 'jardin-events' ),
		);

		register_taxonomy(
			$taxonomy,
			array( $post_type ),
			array(
				'labels'            => $labels,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);

	}

	/**
	 * Register the `event` custom post type.
	 */
	public function register_post_type() {
		$post_type = jardin_events_get_post_type();

		$labels = array(
			'name'               => __( 'Events', 'jardin-events' ),
			'singular_name'      => __( 'Event', 'jardin-events' ),
			'add_new'            => __( 'Add event', 'jardin-events' ),
			'add_new_item'       => __( 'Add new event', 'jardin-events' ),
			'edit_item'          => __( 'Edit event', 'jardin-events' ),
			'new_item'           => __( 'New event', 'jardin-events' ),
			'view_item'          => __( 'View event', 'jardin-events' ),
			'search_items'       => __( 'Search events', 'jardin-events' ),
			'not_found'          => __( 'No events found', 'jardin-events' ),
			'not_found_in_trash' => __( 'No events found in Trash', 'jardin-events' ),
			'all_items'          => __( 'All events', 'jardin-events' ),
			'archives'           => __( 'Event archives', 'jardin-events' ),
			'menu_name'          => __( 'Events', 'jardin-events' ),
		);

		$args = array(
			'label'               => __( 'Events', 'jardin-events' ),
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => true,
			'has_archive'         => true,
			'rewrite'             => array(
				'slug' => jardin_events_get_rewrite_slug(),
			),
			'supports'            => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'custom-fields',
			),
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-calendar-alt',
			'menu_position'       => 6,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_in_admin_bar'   => true,
		);

		$args = apply_filters( 'jardin_events_register_post_type_args', $args );

		register_post_type( $post_type, $args );
	}

	/**
	 * Register post meta for events.
	 */
	public function register_meta() {
		$post_type = jardin_events_get_post_type();

		register_post_meta(
			$post_type,
			'event_date',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_event_date',
			)
		);

		register_post_meta(
			$post_type,
			'event_date_end',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_event_date',
			)
		);

		register_post_meta(
			$post_type,
			'event_city',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_text',
			)
		);

		register_post_meta(
			$post_type,
			'event_country',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_text',
			)
		);

		register_post_meta(
			$post_type,
			'event_map_url',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_url',
			)
		);

		register_post_meta(
			$post_type,
			'event_link',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_url',
			)
		);

		register_post_meta(
			$post_type,
			'event_ticket_url',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_url',
			)
		);


		register_post_meta(
			$post_type,
			'event_article',
			array(
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'array',
				'sanitize_callback' => 'jardin_events_sanitize_meta_event_article',
			)
		);

		register_post_meta(
			$post_type,
			'event_slides_url',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_url',
			)
		);

		register_post_meta(
			$post_type,
			'event_video_url',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'auth_callback'     => array( $this, 'meta_auth_callback' ),
				'type'              => 'string',
				'sanitize_callback' => 'jardin_events_sanitize_meta_url',
			)
		);
	}

	/**
	 * Expose computed fields on REST API for the event post type.
	 */
	public function register_rest_fields() {
		register_rest_field(
			jardin_events_get_post_type(),
			'event_roles',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					return $id ? jardin_events_get_event_roles( $id ) : array();
				},
				'schema'       => array(
					'description' => __( 'Assigned role slugs for this event.', 'jardin-events' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			jardin_events_get_post_type(),
			'event_start',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					if ( $id <= 0 ) {
						return '';
					}
					$start = get_post_meta( $id, 'event_date', true );
					return is_string( $start ) ? $start : '';
				},
				'schema'       => array(
					'description' => __( 'Event start date (Y-m-d).', 'jardin-events' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			jardin_events_get_post_type(),
			'event_end',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					if ( $id <= 0 ) {
						return '';
					}
					$end = function_exists( 'jardin_events_get_event_date_end' ) ? jardin_events_get_event_date_end( $id ) : get_post_meta( $id, 'event_date_end', true );
					return is_string( $end ) ? $end : '';
				},
				'schema'       => array(
					'description' => __( 'Event end date (Y-m-d).', 'jardin-events' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			jardin_events_get_post_type(),
			'event_location',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					if ( $id <= 0 ) {
						return '';
					}
					return function_exists( 'jardin_events_get_event_location_label' ) ? jardin_events_get_event_location_label( $id ) : '';
				},
				'schema'       => array(
					'description' => __( 'Event location text.', 'jardin-events' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			jardin_events_get_post_type(),
			'event_city',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					$v  = $id > 0 ? get_post_meta( $id, 'event_city', true ) : '';
					return is_string( $v ) ? trim( $v ) : '';
				},
				'schema'       => array(
					'description' => __( 'Event city.', 'jardin-events' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			jardin_events_get_post_type(),
			'event_country',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					$v  = $id > 0 ? get_post_meta( $id, 'event_country', true ) : '';
					return is_string( $v ) ? trim( $v ) : '';
				},
				'schema'       => array(
					'description' => __( 'Event country.', 'jardin-events' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			jardin_events_get_post_type(),
			'event_map_url',
			array(
				'get_callback' => static function ( $post ) {
					$id = isset( $post['id'] ) ? (int) $post['id'] : 0;
					$v  = $id > 0 ? get_post_meta( $id, 'event_map_url', true ) : '';
					return is_string( $v ) ? esc_url_raw( trim( $v ) ) : '';
				},
				'schema'       => array(
					'description' => __( 'Event map URL.', 'jardin-events' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
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
		$args = apply_filters(
			'jardin_events_upcoming_query_args',
			array(
				'post_type'      => jardin_events_get_post_type(),
				'posts_per_page' => $limit,
				'meta_query'     => self::build_upcoming_meta_query(),
				'orderby'        => 'meta_value',
				'meta_key'       => 'event_date',
				'meta_type'      => 'DATE',
				'order'          => 'ASC',
			),
			$limit
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
		$args = apply_filters(
			'jardin_events_past_query_args',
			array(
				'post_type'      => jardin_events_get_post_type(),
				'posts_per_page' => $limit,
				'meta_query'     => self::build_past_meta_query(),
				'orderby'        => 'meta_value',
				'meta_key'       => 'event_date',
				'meta_type'      => 'DATE',
				'order'          => 'DESC',
			),
			$limit
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
		$end   = jardin_events_get_event_date_end( $post_id );

		if ( ! $start ) {
			return '';
		}

		$formatted_start = jardin_events_format_ymd_for_display( $start );

		if ( $end ) {
			$formatted_end = jardin_events_format_ymd_for_display( $end );

			if ( '' !== $formatted_end && $formatted_end !== $formatted_start ) {
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
 * Check if jardin-events is active.
 *
 * @return bool
 */
function jardin_events_is_active() {
	return post_type_exists( jardin_events_get_post_type() )
		&& defined( 'JARDIN_EVENTS_VERSION' )
		&& class_exists( 'Jardin_Events_Core', false );
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
