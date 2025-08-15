<?php
/**
 * Render block.
 *
 * @package PollsBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $post;

$vote_options = array();
foreach ( $attributes['options'] as $key => $option ) {
	$unique_id      = wp_unique_id( 'option-' );
	$option['id']   = $key;
	$vote_options[] = $option;
}

$context = array(
	'postId'     => $post->ID,
	'options'    => $vote_options,
	'userVoted'  => false,
	'totalVotes' => 0,
	'blockId'    => $attributes['blockId'],
);

// Set cookie for non-loggedin users.
if ( ! is_user_logged_in() && ! isset( $_COOKIE['poll_anonymous_user_id'] ) && ! empty( $attributes['allowAnonymous'] ) ) {
	$session_id = wp_generate_uuid4();
	setcookie(
		'poll_anonymous_user_id',
		$session_id,
		time() + YEAR_IN_SECONDS
	);
	$_COOKIE['poll_anonymous_user_id'] = $session_id;
}

$meta_key     = 'poll-' . md5( $attributes['blockId'] );
$meta_context = get_post_meta( $post->ID, $meta_key, true );
$meta_context = json_decode( wp_json_encode( $meta_context ), true );

if ( ! empty( $meta_context ) ) {
	$context = $meta_context;
}

$is_user_voted = get_user_meta( get_current_user_id(), $meta_key, true );
$is_user_voted = ! empty( $is_user_voted ) ? intval( $is_user_voted ) : 0;

$context['userVoted']      = btwp_polls_is_user_voted( md5( $attributes['blockId'] ), $attributes['allowAnonymous'], $context['postId'] );
$context['userSelection']  = btwp_polls_user_selection( $is_user_voted, $attributes['allowAnonymous'], md5( $attributes['blockId'] ), $context['postId'] );
$context['isLoggedIn']     = is_user_logged_in();
$context['allowAnonymous'] = $attributes['allowAnonymous'];
$context['canUserVote']    = btwp_polls_can_user_vote( $attributes['allowAnonymous'] );
$context['userSesstionId'] = isset( $_COOKIE['poll_anonymous_user_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['poll_anonymous_user_id'] ) ) : '';

wp_interactivity_state(
	'buntywp-polls',
	array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'btwp_polls_block_nonce' ),
		'totalVote' => 0,
		'userVoted' => $is_user_voted,
	)
);

wp_enqueue_style( 'dashicons' );

?>
<div
	<?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>
	data-wp-interactive="buntywp-polls"
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $context ) ); ?>
	data-wp-watch="callbacks.logIsPollOpen"
>
	<div clas="poll-question">
		<h3><?php echo esc_html( $attributes['question'] ); ?></h3>
	</div>
	<template data-wp-each="context.options">
		<div
			class="poll-option"
			data-wp-class--cantvote="!context.canUserVote"
		>
			<div
				class="poll-option-label"
				data-wp-on--click="actions.toggleVote">
				<span class="poll-option-text" data-wp-text="context.item.option"></span>
				<span class="dashicons dashicons-yes" data-wp-class--hidden="actions.getUserSelection"></span>
				<span class="poll-option-vote" data-wp-text="actions.getPercentage"></span>
			</div>
			<div class="progress-bar"
				data-wp-on--click="actions.toggleVote"
				data-wp-style--width="actions.getPercentage"
				data-wp-class--voted="state.userVoted">
				<div class="progress-fill"></div>
			</div>
		</div>
	</template>
	<div class="poll-footer">
		<div class="total-votes">
			<span data-wp-text="state.totalVoteCount"></span> <?php echo wp_kses_data( _n( 'vote', 'votes', $context['totalVotes'], 'polls-block' ) ); ?>
		</div>
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="user-message" data-wp-class--hidden="context.userVoted">
				<?php
				if ( $attributes['allowAnonymous'] ) {
					esc_html_e( 'Guest voting is enabled - no login required.', 'polls-block' );
				} else {

					$login_link = wp_sprintf(
						/* translators: %s: login url, %s: login text */
						'<a href="%s">%s</a>',
						esc_url( wp_login_url( get_permalink() ) ),
						esc_html__( 'log in', 'polls-block' )
					);

					echo wp_sprintf(
						/* translators: %s: login link */
						esc_html__( 'Please %s to vote in this poll.', 'polls-block' ),
						wp_kses_post( $login_link )
					);

				}
				?>
			</div>
		<?php endif; ?>
		<div class="poll-submit-message" data-wp-class--hidden="!context.userVoted">
			<?php esc_html_e( 'Your vote has been recorded.', 'polls-block' ); ?>
		</div>
	</div>
</div>