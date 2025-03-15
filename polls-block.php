<?php
/**
 * Plugin Name:       Polls Block
 * Description:       Create Polls for your WordPress site using Block.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       polls-block
 *
 * @package PollsBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function blp_polls_block_init() {
	register_block_type( __DIR__ . '/build/polls-block' );
}
add_action( 'init', 'blp_polls_block_init' );

/**
 * Store vote counts.
 */
function handle_poll_vote() {

	// Security check.
	check_ajax_referer( 'poll_vote_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'You must be logged in to vote', 'polls-block' ),
			),
			403
		);
		return;
	}

	if ( empty( $_POST['context'] ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Something went wrong. Try again later.', 'polls-block' ),
			)
		);
	}

	$contex = json_decode( stripslashes( sanitize_text_field( wp_unslash( $_POST['context'] ) ) ) );
	$contex = (array) $contex;

	if ( isset( $contex['item'] ) ) {
		unset( $contex['item'] );
	}

	$user_selection = 0;
	if ( isset( $contex['userSelection'] ) ) {
		$user_selection = $contex['userSelection'];
		unset( $contex['userSelection'] );
	}

	$post_id  = $contex['postId'];
	$block_id = $contex['blockId'];

	$meta_key = 'poll-' . md5( $block_id );

	update_post_meta( $post_id, $meta_key, $contex );
	update_user_meta( get_current_user_id(), $meta_key, $user_selection );

	wp_send_json_success(
		array(
			'message' => esc_html__( 'Vote recorded successfully', 'polls-block' ),
		)
	);
}

add_action( 'wp_ajax_save_poll_vote', 'handle_poll_vote' );
add_action( 'wp_ajax_nopriv_save_poll_vote', 'handle_poll_vote' );
