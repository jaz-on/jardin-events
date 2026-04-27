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
		add_action( 'save_post_event', array( $this, 'save_meta_box' ) );
		add_action( 'admin_notices', array( $this, 'render_date_validation_notice' ) );
	}

	/**
	 * Register the event details meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'jardin_events_details',
			__( 'Détails de l\'événement', 'jardin-events' ),
			array( $this, 'render_meta_box' ),
			'event',
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
		$event_end_date = get_post_meta( $post->ID, 'event_end_date', true );
		$event_location = get_post_meta( $post->ID, 'event_location', true );
		$event_link     = get_post_meta( $post->ID, 'event_link', true );
		$roles_current  = jardin_events_get_event_roles( $post->ID );
		$role_labels    = jardin_events_get_role_labels();
		?>
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
			<label for="jardin-event-location"><?php esc_html_e( 'Lieu', 'jardin-events' ); ?></label><br />
			<input
				type="text"
				id="jardin-event-location"
				name="jardin_event_location"
				value="<?php echo esc_attr( $event_location ); ?>"
				class="widefat"
			/>
		</p>
		<p>
			<label for="jardin-event-link"><?php esc_html_e( 'Lien « En savoir plus »', 'jardin-events' ); ?></label><br />
			<input
				type="url"
				id="jardin-event-link"
				name="jardin_event_link"
				value="<?php echo esc_attr( $event_link ); ?>"
				class="widefat"
				placeholder="https://"
			/>
		</p>
		<fieldset class="jardin-events-roles">
			<legend><?php esc_html_e( 'Roles', 'jardin-events' ); ?></legend>
			<?php foreach ( jardin_events_get_role_slugs() as $slug ) : ?>
				<label style="display:block;margin:0.25em 0;">
					<input
						type="checkbox"
						name="jardin_event_role[]"
						value="<?php echo esc_attr( $slug ); ?>"
						<?php checked( in_array( $slug, $roles_current, true ) ); ?>
					/>
					<?php echo esc_html( isset( $role_labels[ $slug ] ) ? $role_labels[ $slug ] : $slug ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * After save: show notice if dates were invalid (end before start or bad format).
	 */
	public function render_date_validation_notice() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$key = 'jardin_events_invalid_dates_' . $user_id;
		if ( ! get_transient( $key ) ) {
			return;
		}

		delete_transient( $key );
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Les dates de l’événement n’ont pas été enregistrées : vérifiez qu’elles sont valides et que la fin n’est pas avant le début. Le lieu et le lien, eux, ont été enregistrés.', 'jardin-events' ); ?></p>
		</div>
		<?php
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

			$check = jardin_events_validate_event_dates( $parsed_start, $parsed_end );
			if ( is_wp_error( $check ) ) {
				set_transient( 'jardin_events_invalid_dates_' . get_current_user_id(), 1, 45 );
			} else {
				$this->save_parsed_ymd_meta( $post_id, 'event_date', $parsed_start );
				$this->save_parsed_ymd_meta( $post_id, 'event_end_date', $parsed_end );
			}
		}

		if ( isset( $_POST['jardin_event_location'] ) ) {
			$location_value = sanitize_text_field( wp_unslash( $_POST['jardin_event_location'] ) );
			if ( '' === $location_value ) {
				delete_post_meta( $post_id, 'event_location' );
			} else {
				update_post_meta( $post_id, 'event_location', $location_value );
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

		delete_post_meta( $post_id, 'event_role' );
		if ( ! empty( $_POST['jardin_event_role'] ) && is_array( $_POST['jardin_event_role'] ) ) {
			foreach ( wp_unslash( $_POST['jardin_event_role'] ) as $raw_role ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$role = jardin_events_sanitize_event_role_meta( $raw_role );
				if ( '' !== $role ) {
					add_post_meta( $post_id, 'event_role', $role, false );
				}
			}
		}
	}
}
