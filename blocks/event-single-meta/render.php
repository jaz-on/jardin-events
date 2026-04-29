<?php
/**
 * Single event header meta row (mockup .post-meta).
 *
 * @package Jardin_Events
 */

defined( 'ABSPATH' ) || exit;

$event_post_id = (int) get_the_ID();
if ( ! $event_post_id || jardin_events_get_post_type() !== get_post_type( $event_post_id ) ) {
	return '';
}

$start = (string) get_post_meta( $event_post_id, 'event_date', true );
$loc   = function_exists( 'jardin_events_get_event_location_label' ) ? jardin_events_get_event_location_label( $event_post_id ) : '';
$loc   = is_string( $loc ) ? trim( $loc ) : '';

$formatted = class_exists( 'Jardin_Events_Core' ) ? Jardin_Events_Core::format_event_date( $event_post_id ) : '';

$dt_attr = '';
if ( $start && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ) {
	try {
		$tz = wp_timezone();
		$d  = \DateTimeImmutable::createFromFormat( 'Y-m-d', $start, $tz );
		if ( $d ) {
			$dt_attr = $d->setTime( 12, 0 )->format( DATE_ATOM );
		}
	} catch ( \Exception $e ) {
		$dt_attr = $start . 'T12:00:00';
	}
}

$roles_html = function_exists( 'jardin_events_get_role_pills_inline_html' ) ? jardin_events_get_role_pills_inline_html( $event_post_id ) : '';
$today      = class_exists( 'Jardin_Events_Core' ) ? Jardin_Events_Core::get_today_ymd() : gmdate( 'Y-m-d' );
$upcoming   = function_exists( 'jardin_events_is_upcoming' ) ? jardin_events_is_upcoming( $event_post_id ) : false;
$when_class = 'entry-when';
if ( $upcoming && '' !== $start && strcmp( $start, $today ) >= 0 ) {
	$when_class .= ' is-upcoming';
}

ob_start();
?>
<div class="post-meta">
	<?php if ( '' !== $formatted ) : ?>
		<time class="<?php echo esc_attr( $when_class ); ?> dt-start" datetime="<?php echo esc_attr( $dt_attr ); ?>"><?php echo esc_html( $formatted ); ?></time>
	<?php endif; ?>
	<?php if ( '' !== $roles_html ) : ?>
		<span class="event-roles-inline" aria-label="<?php esc_attr_e( 'Mes rôles sur cet événement', 'jardin-events' ); ?>">
			<span class="role-label"><?php esc_html_e( 'rôle :', 'jardin-events' ); ?></span>
			<?php echo $roles_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
		</span>
	<?php endif; ?>
	<?php if ( '' !== $loc ) : ?>
		<span class="p-location"><?php echo esc_html( $loc ); ?></span>
	<?php endif; ?>
</div>
<?php
return ob_get_clean();
