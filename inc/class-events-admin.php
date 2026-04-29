<?php
/**
 * Admin UI for Jardin Events.
 *
 * @package Jardin_Events
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class handling admin meta boxes for events.
 */
class Jardin_Events_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_' . jardin_events_get_post_type(), array( $this, 'save_meta_box' ) );
		add_action( 'admin_notices', array( $this, 'render_meta_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_meta_assets' ) );
		add_action( 'wp_ajax_jardin_events_search_posts', array( $this, 'ajax_search_posts' ) );
	}

	/**
	 * Metabox editor: light layout stylesheet.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin_meta_assets( $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || jardin_events_get_post_type() !== $screen->post_type ) {
			return;
		}
		$path = JARDIN_EVENTS_PLUGIN_DIR . 'assets/css/admin-meta.css';
		if ( is_readable( $path ) ) {
			wp_enqueue_style(
				'jardin-events-admin-meta',
				JARDIN_EVENTS_PLUGIN_URL . 'assets/css/admin-meta.css',
				array(),
				(string) filemtime( $path )
			);
		}

		$js_path = JARDIN_EVENTS_PLUGIN_DIR . 'assets/js/admin-event-article.js';
		if ( ! is_readable( $js_path ) ) {
			return;
		}

		wp_enqueue_script(
			'jardin-events-admin-article',
			JARDIN_EVENTS_PLUGIN_URL . 'assets/js/admin-event-article.js',
			array(),
			(string) filemtime( $js_path ),
			true
		);

		wp_localize_script(
			'jardin-events-admin-article',
			'jardinEventsAdmin',
			array(
				'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'jardin_events_article_search' ),
			)
		);
	}

	/**
	 * Register the event details meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'jardin_events_details',
			__( 'Informations', 'jardin-events' ),
			array( $this, 'render_meta_box' ),
			jardin_events_get_post_type(),
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box fields.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'jardin_events_save_meta', 'jardin_events_nonce' );

		$event_date     = get_post_meta( $post->ID, 'event_date', true );
		$event_end_date = jardin_events_get_event_date_end( $post->ID );
		$event_city     = get_post_meta( $post->ID, 'event_city', true );
		$event_country  = get_post_meta( $post->ID, 'event_country', true );
		$event_map_url  = get_post_meta( $post->ID, 'event_map_url', true );
		$event_link     = get_post_meta( $post->ID, 'event_link', true );
		$event_ticket   = get_post_meta( $post->ID, 'event_ticket_url', true );
		$event_slides   = get_post_meta( $post->ID, 'event_slides_url', true );
		$event_video    = get_post_meta( $post->ID, 'event_video_url', true );
		$linked_post    = jardin_events_get_event_article_id( $post->ID );
		?>
		<div class="jardin-events-meta">
		<p>
			<label for="jardin-event-date"><?php esc_html_e( 'Date de début', 'jardin-events' ); ?></label><br />
			<input
				type="date"
				id="jardin-event-date"
				name="jardin_event_date"
				value="<?php echo esc_attr( $event_date ); ?>"
				class="widefat"
			/>
		</p>
		<p>
			<label for="jardin-event-end-date"><?php esc_html_e( 'Date de fin (optionnelle)', 'jardin-events' ); ?></label><br />
			<input
				type="date"
				id="jardin-event-end-date"
				name="jardin_event_end_date"
				value="<?php echo esc_attr( $event_end_date ); ?>"
				class="widefat"
			/>
		</p>
		<p>
			<label for="jardin-event-city"><?php esc_html_e( 'Ville', 'jardin-events' ); ?></label><br />
			<input
				type="text"
				id="jardin-event-city"
				name="jardin_event_city"
				value="<?php echo esc_attr( (string) $event_city ); ?>"
				class="widefat"
			/>
		</p>
		<p>
			<label for="jardin-event-country"><?php esc_html_e( 'Pays', 'jardin-events' ); ?></label><br />
			<input
				type="text"
				id="jardin-event-country"
				name="jardin_event_country"
				value="<?php echo esc_attr( (string) $event_country ); ?>"
				class="widefat"
			/>
		</p>
		<p>
			<label for="jardin-event-map-url"><?php esc_html_e( 'Lien carte (Google Maps/OSM, optionnel)', 'jardin-events' ); ?></label><br />
			<input
				type="url"
				id="jardin-event-map-url"
				name="jardin_event_map_url"
				value="<?php echo esc_attr( (string) $event_map_url ); ?>"
				class="widefat"
				placeholder="https://"
			/>
		</p>
		<p>
			<label for="jardin-event-link"><?php esc_html_e( 'Page de l’événement', 'jardin-events' ); ?></label><br />
			<input
				type="url"
				id="jardin-event-link"
				name="jardin_event_link"
				value="<?php echo esc_attr( $event_link ); ?>"
				class="widefat"
				placeholder="https://"
			/>
		</p>
		<p>
			<label for="jardin-event-ticket-url"><?php esc_html_e( 'Billetterie (optionnel)', 'jardin-events' ); ?></label><br />
			<input
				type="url"
				id="jardin-event-ticket-url"
				name="jardin_event_ticket_url"
				value="<?php echo esc_attr( (string) $event_ticket ); ?>"
				class="widefat"
				placeholder="https://"
			/>
		</p>
		<p class="jardin-events-article-row">
			<label for="jardin-event-article-search"><?php esc_html_e( 'Contenu lié (récap)', 'jardin-events' ); ?></label><br />
			<input
				type="search"
				id="jardin-event-article-search"
				class="widefat jardin-events-article-search"
				value=""
				autocomplete="off"
				placeholder="<?php esc_attr_e( 'Rechercher un contenu lié par titre…', 'jardin-events' ); ?>"
			/>
			<input type="hidden" name="jardin_event_article" id="jardin-event-article-id" value="<?php echo $linked_post ? esc_attr( (string) $linked_post ) : ''; ?>" />
			<ul class="jardin-events-article-suggest" id="jardin-event-article-suggest" hidden></ul>
			<?php if ( $linked_post ) : ?>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %d: post ID */ __( 'ID sélectionné : %d', 'jardin-events' ), $linked_post ) ); ?></span>
			<?php endif; ?>
		</p>
		<p>
			<label for="jardin-event-slides"><?php esc_html_e( 'URL des slides (optionnel)', 'jardin-events' ); ?></label><br />
			<input
				type="url"
				id="jardin-event-slides"
				name="jardin_event_slides_url"
				value="<?php echo esc_attr( (string) $event_slides ); ?>"
				class="widefat"
				placeholder="https://"
			/>
		</p>
		<p>
			<label for="jardin-event-video"><?php esc_html_e( 'URL vidéo (optionnel)', 'jardin-events' ); ?></label><br />
			<input
				type="url"
				id="jardin-event-video"
				name="jardin_event_video_url"
				value="<?php echo esc_attr( (string) $event_video ); ?>"
				class="widefat"
				placeholder="https://"
			/>
		</p>
		</div>
		<?php
	}

	/**
	 * After save: show notice if dates were invalid (end before start or bad format).
	 */
	public function render_meta_notices() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$key_bad = 'jardin_events_invalid_dates_' . $user_id;
		if ( get_transient( $key_bad ) ) {
			delete_transient( $key_bad );
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Les dates de l’événement n’ont pas été enregistrées : vérifiez qu’elles sont valides et que la fin n’est pas avant le début. Les autres champs ont pu être enregistrés.', 'jardin-events' ); ?></p>
			</div>
			<?php
		}

		$key_miss = 'jardin_events_missing_start_' . $user_id;
		if ( get_transient( $key_miss ) ) {
			delete_transient( $key_miss );
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'La date de début est obligatoire : les dates n’ont pas été enregistrées.', 'jardin-events' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX: search published posts by title for recap picker.
	 */
	public function ajax_search_posts() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		check_ajax_referer( 'jardin_events_article_search', 'nonce' );

		$term = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$q = new WP_Query(
			array(
				'post_type'              => jardin_events_get_event_article_post_types(),
				'post_status'            => 'publish',
				's'                      => $term,
				'posts_per_page'         => 8,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'ignore_sticky_posts'    => true,
			)
		);

		$out = array();
		foreach ( $q->posts as $p ) {
			$out[] = array(
				'id'    => (int) $p->ID,
				'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			);
		}

		wp_reset_postdata();
		wp_send_json_success( $out );
	}

	/**
	 * Persist a parsed Y-m-d or empty meta value.
	 *
	 * @param int         $post_id  Post ID.
	 * @param string      $meta_key Meta key.
	 * @param string|null $parsed   Empty string, Y-m-d, or null if invalid (should not reach save when null).
	 */
	private function save_parsed_ymd_meta( $post_id, $meta_key, $parsed ) {
		if ( null === $parsed || '' === $parsed ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $parsed );
	}

	/**
	 * Save meta box values.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['jardin_events_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jardin_events_nonce'] ) ), 'jardin_events_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['jardin_event_date'], $_POST['jardin_event_end_date'] ) ) {
			$raw_start    = sanitize_text_field( wp_unslash( $_POST['jardin_event_date'] ) );
			$raw_end      = sanitize_text_field( wp_unslash( $_POST['jardin_event_end_date'] ) );
			$parsed_start = jardin_events_parse_ymd_meta( $raw_start );
			$parsed_end   = jardin_events_parse_ymd_meta( $raw_end );

			$check = jardin_events_validate_event_dates(
				$parsed_start,
				$parsed_end,
				array( 'require_non_empty_start' => true )
			);
			if ( is_wp_error( $check ) ) {
				$code = $check->get_error_code();
				if ( 'jardin_events_missing_start' === $code ) {
					set_transient( 'jardin_events_missing_start_' . get_current_user_id(), 1, 45 );
				} else {
					set_transient( 'jardin_events_invalid_dates_' . get_current_user_id(), 1, 45 );
				}
			} else {
				$this->save_parsed_ymd_meta( $post_id, 'event_date', $parsed_start );
				$this->save_parsed_ymd_meta( $post_id, 'event_date_end', $parsed_end );
			}
		}

		if ( isset( $_POST['jardin_event_city'] ) ) {
			$city_value = sanitize_text_field( wp_unslash( $_POST['jardin_event_city'] ) );
			if ( '' === $city_value ) {
				delete_post_meta( $post_id, 'event_city' );
			} else {
				update_post_meta( $post_id, 'event_city', $city_value );
			}
		}

		if ( isset( $_POST['jardin_event_country'] ) ) {
			$country_value = sanitize_text_field( wp_unslash( $_POST['jardin_event_country'] ) );
			if ( '' === $country_value ) {
				delete_post_meta( $post_id, 'event_country' );
			} else {
				update_post_meta( $post_id, 'event_country', $country_value );
			}
		}

		if ( isset( $_POST['jardin_event_map_url'] ) ) {
			$map_value = esc_url_raw( wp_unslash( $_POST['jardin_event_map_url'] ) );
			if ( '' === $map_value ) {
				delete_post_meta( $post_id, 'event_map_url' );
			} else {
				update_post_meta( $post_id, 'event_map_url', $map_value );
			}
		}

		if ( isset( $_POST['jardin_event_link'] ) ) {
			$link_value = esc_url_raw( wp_unslash( $_POST['jardin_event_link'] ) );
			if ( '' === $link_value ) {
				delete_post_meta( $post_id, 'event_link' );
			} else {
				update_post_meta( $post_id, 'event_link', $link_value );
			}
		}

		if ( isset( $_POST['jardin_event_ticket_url'] ) ) {
			$ticket_value = esc_url_raw( wp_unslash( $_POST['jardin_event_ticket_url'] ) );
			if ( '' === $ticket_value ) {
				delete_post_meta( $post_id, 'event_ticket_url' );
			} else {
				update_post_meta( $post_id, 'event_ticket_url', $ticket_value );
			}
		}

		if ( isset( $_POST['jardin_event_article'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['jardin_event_article'] ) );
			if ( '' === trim( $raw ) ) {
				delete_post_meta( $post_id, 'event_article' );
			} else {
				$lid = jardin_events_sanitize_meta_event_article( absint( $raw ) );
				if ( $lid > 0 ) {
					update_post_meta( $post_id, 'event_article', $lid );
				} else {
					delete_post_meta( $post_id, 'event_article' );
				}
			}
		}

		if ( isset( $_POST['jardin_event_slides_url'] ) ) {
			$slides = esc_url_raw( wp_unslash( $_POST['jardin_event_slides_url'] ) );
			if ( '' === $slides ) {
				delete_post_meta( $post_id, 'event_slides_url' );
			} else {
				update_post_meta( $post_id, 'event_slides_url', $slides );
			}
		}

		if ( isset( $_POST['jardin_event_video_url'] ) ) {
			$video = esc_url_raw( wp_unslash( $_POST['jardin_event_video_url'] ) );
			if ( '' === $video ) {
				delete_post_meta( $post_id, 'event_video_url' );
			} else {
				update_post_meta( $post_id, 'event_video_url', $video );
			}
		}

	}
}
