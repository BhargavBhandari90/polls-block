<?php
/**
 * Plugin Name:       Polls Block
 * Description:       Create Interactive Polls for your WordPress site using Block.
 * Version:           1.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            BuntyWP
 * Author URI:        https://biliplugins.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       polls-block
 *
 * @package PollsBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'BTWP_POLLS_VERSION' ) ) {
	/**
	 * The version of the plugin.
	 */
	define( 'BTWP_POLLS_VERSION', '1.1.0' );
}
if ( ! defined( 'BTWP_POLLS_PATH' ) ) {
	/**
	 *  The server file system path to the plugin directory.
	 */
	define( 'BTWP_POLLS_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! function_exists( 'btwp_polls_block_init' ) ) {
	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 * Behind the scenes, it registers also all assets so they can be enqueued
	 * through the block editor in the corresponding context.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	function btwp_polls_block_init() {
		register_block_type( __DIR__ . '/build/polls-block' );
	}
}
add_action( 'init', 'btwp_polls_block_init' );

/**
 * Store vote counts.
 */
function btwp_polls_handle_poll_vote() {

	// Security check.
	check_ajax_referer( 'btwp_polls_block_nonce', 'nonce' );

	if ( empty( $_POST['context'] ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Something went wrong. Try again later.', 'polls-block' ),
			)
		);
	}

	$contex = json_decode( stripslashes( sanitize_text_field( wp_unslash( $_POST['context'] ) ) ) );
	$contex = (array) $contex;

	$allow_nonymous = $contex['allowAnonymous'];

	if ( ! is_user_logged_in() && ! $allow_nonymous ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'You must be logged in to vote', 'polls-block' ),
			),
			403
		);
		return;
	}

	if ( isset( $contex['item'] ) ) {
		unset( $contex['item'] );
	}

	$user_selection = 0;
	if ( isset( $contex['userSelection'] ) ) {
		$user_selection = $contex['userSelection'];
		unset( $contex['userSelection'] );
	}

	++$user_selection;

	$post_id  = $contex['postId'];
	$block_id = $contex['blockId'];
	$meta_key = 'poll-' . md5( $block_id );

	update_post_meta( $post_id, $meta_key, $contex );

	if ( ! is_user_logged_in() && $allow_nonymous ) {
		$anonymous_meta_key    = 'poll-anonymous-' . md5( $block_id );
		$anonymous_voted_users = get_post_meta( $post_id, $anonymous_meta_key, true );
		$anonymous_voted_users = ! empty( $anonymous_voted_users ) ? $anonymous_voted_users : array();

		if ( ! empty( $anonymous_voted_users ) && isset( $anonymous_voted_users[ $meta_key ] ) ) {
			$anonymous_voted_users[ $meta_key ][ $contex['userSesstionId'] ] = $user_selection;
		} else {
			$anonymous_voted_users = array(
				$meta_key => array(
					$contex['userSesstionId'] => $user_selection,
				),
			);
		}

		update_post_meta( $post_id, $anonymous_meta_key, $anonymous_voted_users );
	} else {
		update_user_meta( get_current_user_id(), $meta_key, $user_selection );
	}

	wp_send_json_success(
		array(
			'message' => esc_html__( 'Vote recorded successfully', 'polls-block' ),
		)
	);
}

add_action( 'wp_ajax_save_poll_vote', 'btwp_polls_handle_poll_vote' );
add_action( 'wp_ajax_nopriv_save_poll_vote', 'btwp_polls_handle_poll_vote' );

// Include files.
require BTWP_POLLS_PATH . 'app/includes/common-functions.php';
