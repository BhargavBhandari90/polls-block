import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'buntywp-polls', {
	state: {
		get totalVoteCount() {
			const context = getContext();
			return context.totalVotes;
		},
		get userLoggedin() {
			const context = getContext();
			return context.isLoggedIn;
		},
	},
	actions: {
		toggleVote: () => {
			const context = getContext();

			// Return, if already voted or not loggedin.
			if ( context.userVoted || ! context.canUserVote ) {
				return;
			}

			let index = context.item.id;
			context.options[ index ].votes =
				( context.options[ index ].votes || 0 ) + 1;
			context.userSelection = index;
			context.options = context.options;
			context.totalVotes = Number( context.totalVotes + 1 );
			context.userVoted = true;
			saveVoteToServer( context );
		},
		getPercentage: () => {
			const context = getContext();
			if ( context.totalVotes === 0 ) {
				return '0%';
			}
			let index = context.item.id;
			const percentage =
				( context.options[ index ].votes / context.totalVotes ) * 100;
			return `${ percentage.toFixed( 0 ) }%`;
		},

		getUserSelection: () => {
			const context = getContext();
			let index = context.item.id;
			return ! (
				parseInt( context.options[ index ].id ) ===
				parseInt( context.userSelection )
			);
		},
	},
	callbacks: {
		logIsPollOpen: () => {
			const { isOpen } = getContext();
		},
	},
} );

/**
 * Save the vote to the server via AJAX.
 *
 * @param {object} context Poll Context.
 */
function saveVoteToServer( context ) {
	fetch( state.ajaxUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: new URLSearchParams( {
			action: 'save_poll_vote',
			nonce: state.nonce,
			context: JSON.stringify( context ),
		} ),
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			console.log( 'Vote saved:', data );
		} )
		.catch( ( error ) => {
			console.error( 'Error saving vote:', error );
		} );
}
