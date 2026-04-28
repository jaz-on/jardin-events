<?php
/**
 * Status bar for singular events (mockup .event-status-bar).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'jardin_events_status_label' ) ) {
	return '';
}

$event_post_id = (int) get_the_ID();
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$label       = jardin_events_status_label( $event_post_id );
$countdown   = jardin_events_countdown_text( $event_post_id );
$past_badge  = ! jardin_events_is_upcoming( $event_post_id );
$badge_class = 'event-status-badge' . ( $past_badge ? ' event-status-past' : '' );

ob_start();
?>
<div class="event-status-bar">
	<span class="<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $label ); ?></span>
	<?php if ( '' !== $countdown ) : ?>
		<span class="event-days-left"><?php echo esc_html( $countdown ); ?></span>
	<?php endif; ?>
</div>
<?php
return ob_get_clean();
