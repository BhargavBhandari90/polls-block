<?php
/**
 * Common functions for the Polls Block plugin.
 *
 * @package PollsBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly for security.
}

/**
 * Determine whether the current user is allowed to vote.
 *
 * This function checks if the current visitor can cast a vote based on:
 * - Whether they are logged in.
 * - Whether anonymous voting is allowed.
 *
 * @param bool $allow_anonymous Optional. Whether to allow anonymous (non-logged-in) users to vote. Default false.
 *
 * @return bool True if the user can vote, false otherwise.
 */
function btwp_polls_can_user_vote( $allow_anonymous = false ) {

	$can_vote = false;

	if ( is_user_logged_in() ) {
		$can_vote = true;
	} elseif ( $allow_anonymous ) {
		$can_vote = true;
	} else {
		$can_vote = false;
	}

	return $can_vote;
}

/**
 * Check if the current user (logged-in or anonymous) has already voted.
 *
 * For logged-in users, this checks their user meta.
 * For anonymous users, this checks based on their anonymous user ID cookie.
 *
 * @param string $block_id         Unique ID of the poll block.
 * @param bool   $allow_anonymous  Optional. Whether anonymous voting is allowed. Default false.
 * @param int    $post_id          Optional. The post ID containing the poll. Default 0.
 *
 * @return bool True if the user has already voted, false otherwise.
 */
function btwp_polls_is_user_voted( $block_id = '', $allow_anonymous = false, $post_id = 0 ) {

	$user_voted = false;

	if ( empty( $block_id ) ) {
		return false;
	}

	$meta_key = 'poll-' . $block_id;

	if ( is_user_logged_in() ) {
		$user_voted = get_user_meta( get_current_user_id(), $meta_key, true );
		$user_voted = ! empty( $user_voted ) ? true : false;
	}

	if ( $allow_anonymous && ! is_user_logged_in() ) {
		$user_id    = isset( $_COOKIE['poll_anonymous_user_id'] ) ? $_COOKIE['poll_anonymous_user_id'] : ''; // phpcs:ignore
		$user_voted = ! empty( $user_id ) ? btwp_polls_is_anonymous_user_voted( $block_id, $user_id, $post_id ) : false;
	}

	return $user_voted;
}

/**
 * Check if an anonymous user has already voted on a poll.
 *
 * This function checks the post meta to see if the given anonymous user ID
 * exists in the list of anonymous voters for the given poll block.
 *
 * @param string $block_id Unique ID of the poll block.
 * @param string $user_id  Unique anonymous user ID (usually stored in a cookie).
 * @param int    $post_id  The post ID containing the poll.
 *
 * @return bool True if the anonymous user has already voted, false otherwise.
 */
function btwp_polls_is_anonymous_user_voted( $block_id = '', $user_id = '', $post_id = 0 ) {

	if ( empty( $block_id ) || empty( $user_id ) || empty( $post_id ) ) {
		return false;
	}

	$user_voted         = false;
	$meta_key           = 'poll-' . $block_id;
	$anonymous_meta_key = 'poll-anonymous-' . $block_id;

	// Retrieve all anonymous votes for this poll.
	$anonymous_voted_users = get_post_meta( $post_id, $anonymous_meta_key, true );

	if ( ! empty( $anonymous_voted_users ) && isset( $anonymous_voted_users[ $meta_key ] ) ) {
		// If the anonymous user ID exists in the recorded votes array, they have already voted.
		if ( isset( $anonymous_voted_users[ $meta_key ][ $user_id ] ) ) {
			$user_voted = true;
		}
	}

	return $user_voted;
}
/**
 * Get User's selected poll option.
 *
 * @param string  $user_selection User selection idenx.
 * @param bool    $allow_anonymous If anonymous voting is allowed.
 * @param string  $block_id Block Id.
 * @param integer $post_id Post Id.
 * @return integer
 */
function btwp_polls_user_selection( $user_selection = '', $allow_anonymous = false, $block_id = '', $post_id = 0 ) {

	if ( empty( $user_selection ) || empty( $block_id ) ) {
		$user_selection = '';
	}

	if ( $allow_anonymous && ! is_user_logged_in() ) {

		$meta_key           = 'poll-' . $block_id;
		$anonymous_meta_key = 'poll-anonymous-' . $block_id;
		$user_id            = isset( $_COOKIE['poll_anonymous_user_id'] ) ? $_COOKIE['poll_anonymous_user_id'] : '';

		if ( ! empty( $user_id ) ) {
			$anonymous_voted_users = get_post_meta( $post_id, $anonymous_meta_key, true );

			if ( ! empty( $anonymous_voted_users ) ) {
				$user_selection = isset( $anonymous_voted_users[ $meta_key ][ $user_id ] ) ? intval( $anonymous_voted_users[ $meta_key ][ $user_id ] ) : 0;
			}
		}
	}

	$user_selection = ( $user_selection > 0 ) ? --$user_selection : '';

	return $user_selection;
}
