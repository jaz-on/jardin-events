<?php
/**
 * Tests for event_role helpers.
 *
 * @package Jardin_Events
 */

/**
 * @group jardin-events
 */
class Jardin_Events_Event_Roles_Test extends WP_UnitTestCase {

	/**
	 * Sanitize rejects unknown slugs.
	 */
	public function test_sanitize_event_role_meta() {
		$taxonomy = jardin_events_get_role_taxonomy();
		if ( ! term_exists( 'speaker', $taxonomy ) ) {
			self::factory()->term->create(
				array(
					'taxonomy' => $taxonomy,
					'slug'     => 'speaker',
					'name'     => 'Speaker',
				)
			);
		}
		$this->assertSame( 'speaker', jardin_events_sanitize_event_role_meta( 'speaker' ) );
		$this->assertSame( '', jardin_events_sanitize_event_role_meta( 'hacker' ) );
		$this->assertSame( '', jardin_events_sanitize_event_role_meta( '' ) );
	}

	/**
	 * Stored roles round-trip via taxonomy terms.
	 */
	public function test_get_event_roles_from_taxonomy() {
		self::factory()->term->create(
			array(
				'taxonomy' => jardin_events_get_role_taxonomy(),
				'slug'     => 'speaker',
				'name'     => 'Speaker',
			)
		);
		self::factory()->term->create(
			array(
				'taxonomy' => jardin_events_get_role_taxonomy(),
				'slug'     => 'organizer',
				'name'     => 'Organizer',
			)
		);
		$post_id = self::factory()->post->create( array( 'post_type' => 'event' ) );
		wp_set_object_terms( $post_id, array( 'speaker', 'organizer' ), jardin_events_get_role_taxonomy() );
		$this->assertEqualsCanonicalizing(
			array( 'speaker', 'organizer' ),
			jardin_events_get_event_roles( $post_id )
		);
		$this->assertTrue( jardin_events_has_role( $post_id, 'speaker' ) );
		$this->assertFalse( jardin_events_has_role( $post_id, 'sponsor' ) );
	}
}
