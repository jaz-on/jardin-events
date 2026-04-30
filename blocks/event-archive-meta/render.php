<?php
/**
 * Archive list meta: roles + location (mockup .entry-meta).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = (int) get_the_ID();
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$roles        = function_exists( 'jardin_events_get_event_roles' ) ? jardin_events_get_event_roles( $event_post_id ) : array();
$primary_role = ! empty( $roles ) ? (string) $roles[0] : '';
$roles_attr   = ! empty( $roles ) ? implode( ',', array_map( 'sanitize_key', $roles ) ) : '';
$pills        = function_exists( 'jardin_events_get_role_pills_html' ) ? jardin_events_get_role_pills_html( $event_post_id ) : '';
$loc          = function_exists( 'jardin_events_get_event_location_label' ) ? jardin_events_get_event_location_label( $event_post_id ) : '';
$loc          = is_string( $loc ) ? trim( $loc ) : '';

if ( '' === $pills ) {
	$pills = sprintf(
		'<span class="entry-role entry-role--unknown">%s</span>',
		esc_html__( 'Event', 'jardin-events' )
	);
}

if ( '' === $primary_role ) {
	$primary_role = 'unknown';
}

ob_start();
?>
<div class="entry-meta" data-primary-role="<?php echo esc_attr( $primary_role ); ?>" data-event-roles="<?php echo esc_attr( $roles_attr ); ?>">
	<?php
	echo $pills // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML built with esc_* in helper.
	?>
	<?php if ( '' !== $loc ) : ?>
		<span class="entry-loc"><?php echo esc_html( $loc ); ?></span>
	<?php endif; ?>
</div>
<?php
return ob_get_clean();
