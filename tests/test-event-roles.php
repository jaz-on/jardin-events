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
		$this->assertSame( 'speaker', jardin_events_sanitize_event_role_meta( 'speaker' ) );
		$this->assertSame( '', jardin_events_sanitize_event_role_meta( 'hacker' ) );
		$this->assertSame( '', jardin_events_sanitize_event_role_meta( '' ) );
	}

	/**
	 * Stored roles round-trip via post meta.
	 */
	public function test_get_event_roles_from_meta() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'event' ) );
		add_post_meta( $post_id, 'event_role', 'speaker', false );
		add_post_meta( $post_id, 'event_role', 'organizer', false );
		$this->assertSame( array( 'speaker', 'organizer' ), jardin_events_get_event_roles( $post_id ) );
		$this->assertTrue( jardin_events_has_role( $post_id, 'speaker' ) );
		$this->assertFalse( jardin_events_has_role( $post_id, 'sponsor' ) );
	}
}
