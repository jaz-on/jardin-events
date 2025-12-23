<?php
/**
 * Pattern: Prochains événements (plugin default).
 *
 * @package Jardin_Events
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
			'jardin-event/upcoming-events',
			array(
				'title'       => __( 'Prochains événements (Jardin Events)', 'jardin-events' ),
				'description' => __( 'Liste simple des prochains événements.', 'jardin-events' ),
				'categories'  => array( 'query', 'widgets' ),
				'content'     =>
					'<!-- wp:group {"className":"jardin-events-upcoming","layout":{"type":"constrained"}} -->' .
					'<div class="wp-block-group jardin-events-upcoming">' .
					'<!-- wp:heading {"level":2} --><h2>Prochains événements</h2><!-- /wp:heading -->' .
					'<!-- wp:separator {"className":"is-style-wide"} --><hr class="wp-block-separator is-style-wide" /><!-- /wp:separator -->' .
					'<!-- wp:query {"query":{"perPage":3,"postType":"event","order":"asc","orderBy":"date","inherit":false},"displayLayout":{"type":"list"}} -->' .
					'<div class="wp-block-query"><!-- wp:post-template -->' .
					'<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group jardin-events-item">' .
					'<!-- wp:post-title {"level":3,"className":"jardin-events-item-title"} /-->' .
					'<!-- wp:post-excerpt {"showMoreOnNewLine":false} /-->' .
					'<!-- wp:post-date {"className":"jardin-events-item-meta"} /-->' .
					'</div><!-- /wp:group -->' .
					'<!-- /wp:post-template --></div><!-- /wp:query -->' .
					'</div><!-- /wp:group -->',
			)
		);
	}
);


