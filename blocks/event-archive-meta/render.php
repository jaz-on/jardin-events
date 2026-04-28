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

$pills = function_exists( 'jardin_events_get_role_pills_html' ) ? jardin_events_get_role_pills_html( $event_post_id ) : '';
$loc   = get_post_meta( $event_post_id, 'event_location', true );
$loc   = is_string( $loc ) ? trim( $loc ) : '';

if ( '' === $pills && '' === $loc ) {
	return '';
}

ob_start();
?>
<div class="entry-meta">
	<?php
	echo $pills // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML built with esc_* in helper.
	?>
	<?php if ( '' !== $loc ) : ?>
		<span class="entry-loc"><?php echo esc_html( $loc ); ?></span>
	<?php endif; ?>
</div>
<?php
return ob_get_clean();
