<?php
/**
 * Tests for event date parsing and validation helpers.
 *
 * @package Jardin_Events
 */

/**
 * @group jardin-events
 */
class Jardin_Events_Event_Meta_Validation_Test extends WP_UnitTestCase {

	/**
	 * Empty and valid Y-m-d parse as empty or same string; garbage returns null.
	 */
	public function test_parse_ymd_meta_accepts_empty_and_valid_and_rejects_garbage() {
		$this->assertSame( '', jardin_events_parse_ymd_meta( '' ) );
		$this->assertSame( '2026-04-20', jardin_events_parse_ymd_meta( '2026-04-20' ) );
		$this->assertNull( jardin_events_parse_ymd_meta( 'not-a-date' ) );
		$this->assertNull( jardin_events_parse_ymd_meta( '2026-13-40' ) );
	}

	/**
	 * Valid range returns true; end before start returns WP_Error.
	 */
	public function test_validate_event_dates_range() {
		$this->assertTrue( jardin_events_validate_event_dates( '', '' ) );
		$this->assertTrue( jardin_events_validate_event_dates( '2026-04-20', '2026-04-21' ) );
		$error = jardin_events_validate_event_dates( '2026-04-21', '2026-04-20' );
		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertSame( 'jardin_events_invalid_range', $error->get_error_code() );
	}

	/**
	 * Invalid month/day yields WP_Error on the appropriate code.
	 */
	public function test_validate_event_dates_invalid_format() {
		$error = jardin_events_validate_event_dates( null, '' );
		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertSame( 'jardin_events_invalid_date', $error->get_error_code() );
	}

	/**
	 * Optional flag rejects empty start when required (REST / admin save).
	 */
	public function test_validate_event_dates_requires_start_when_flagged() {
		$error = jardin_events_validate_event_dates(
			'',
			'',
			array( 'require_non_empty_start' => true )
		);
		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertSame( 'jardin_events_missing_start', $error->get_error_code() );
	}
}
