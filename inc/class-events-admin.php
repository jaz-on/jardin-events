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
		<?php
	}

	/**
	 * After save: show notice if end date was before start date.
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
			<p><?php esc_html_e( 'La date de fin doit être identique ou postérieure à la date de début. Les modifications n’ont pas été enregistrées.', 'jardin-events' ); ?></p>
		</div>
		<?php
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

		$start_input = isset( $_POST['jardin_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['jardin_event_date'] ) ) : null;
		$end_input   = isset( $_POST['jardin_event_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['jardin_event_end_date'] ) ) : null;

		if ( null !== $start_input && null !== $end_input && '' !== $start_input && '' !== $end_input && strcmp( $end_input, $start_input ) < 0 ) {
			set_transient( 'jardin_events_invalid_dates_' . get_current_user_id(), 1, 45 );
			return;
		}

		$fields = array(
			'event_date'     => 'jardin_event_date',
			'event_end_date' => 'jardin_event_end_date',
			'event_location' => 'jardin_event_location',
			'event_link'     => 'jardin_event_link',
		);

		foreach ( $fields as $meta_key => $field_name ) {
			if ( ! isset( $_POST[ $field_name ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );

			if ( 'event_link' === $meta_key ) {
				$value = esc_url_raw( $value );
			}

			if ( '' === $value ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}
