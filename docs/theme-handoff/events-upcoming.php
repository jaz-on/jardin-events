<?php
/**
 * Pattern sample: upcoming events (drop into jardin-theme as patterns/events-upcoming.php if useful).
 *
 * @package Jardin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

add_action(
	'init',
	function () {
		register_block_pattern(
			'jardin/upcoming-events',
			array(
				'title'       => __( 'Upcoming events', 'jardin-events' ),
				'description' => __( 'Upcoming events list (dates and location via jardin-events meta).', 'jardin-events' ),
				'categories'  => array( 'query', 'featured' ),
				'content'     =>
					'<!-- wp:group {"className":"jardin-events-upcoming","layout":{"type":"constrained"}} -->' .
					'<div class="wp-block-group jardin-events-upcoming">' .
					'<!-- wp:heading {"level":2} --><h2>' . __( 'Upcoming events', 'jardin-events' ) . '</h2><!-- /wp:heading -->' .
					'<!-- wp:separator {"className":"is-style-wide"} --><hr class="wp-block-separator is-style-wide" /><!-- /wp:separator -->' .
					'<!-- wp:query {"className":"jardin-events-query--upcoming","query":{"perPage":3,"postType":"event","order":"asc","orderBy":"date","inherit":false},"displayLayout":{"type":"list"}} -->' .
					'<div class="wp-block-query">' .
					'<!-- wp:post-template -->' .
					'<!-- wp:group {"layout":{"type":"constrained"},"className":"jardin-events-item"} -->' .
					'<div class="wp-block-group jardin-events-item">' .
					'<!-- wp:post-title {"level":3,"className":"jardin-events-item-title"} /-->' .
					'<!-- wp:post-excerpt {"showMoreOnNewLine":false} /-->' .
					'<!-- wp:post-meta {"key":"event_date","className":"jardin-events-item-meta"} /-->' .
					'<!-- wp:post-meta {"key":"event_location","className":"jardin-events-item-meta"} /-->' .
					'<!-- wp:post-meta {"key":"event_link","className":"jardin-events-item-meta"} /-->' .
					'</div><!-- /wp:group -->' .
					'<!-- /wp:post-template -->' .
					'</div><!-- /wp:query -->' .
					'</div><!-- /wp:group -->',
			)
		);
	}
);
