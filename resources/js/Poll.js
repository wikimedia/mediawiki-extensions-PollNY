/**
 * JavaScript for PollNY extension
 * The PollNY object here contains almost all the JS that the extension needs.
 * Previously these JS bits and pieces were scattered over in different places.
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 */
var PollNY = {
	voted: 0,

	/**
	 * @return Boolean: true if the browser is Firefox under Mac
	 */
	detectMacXFF: function () {
		var userAgent = navigator.userAgent.toLowerCase();
		if ( userAgent.indexOf( 'mac' ) != -1 && userAgent.indexOf( 'firefox' ) != -1 ) {
			return true;
		}
	},

	show: function () {
		var loadingElement = document.getElementById( 'loading-poll' ),
			displayElement = document.getElementById( 'poll-display' );
		if ( loadingElement ) {
			loadingElement.style.display = 'none';
			loadingElement.style.visibility = 'hidden';
		}
		if ( displayElement ) {
			displayElement.style.display = 'block';
			displayElement.style.visibility = 'visible';
		}
	},

	/**
	 * Show the "Loading..." text in the lightbox; Firefox on Mac gets only
	 * that whereas all other User-Agents get the pretty animation.
	 */
	loadingLightBox: function () {
		// pop up the lightbox
		var objLink = {};
		objLink.href = '';
		objLink.title = '';

		window.LightBox.show( objLink );

		if ( !PollNY.detectMacXFF() && window.isFlashSupported() ) {
			window.LightBox.setText(
				'<embed src="' + mw.config.get( 'wgExtensionAssetsPath' ) + '/SocialProfile/images/ajax-loading.swf" quality="high" wmode="transparent" bgcolor="#ffffff"' +
				'pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash"' +
				'type="application/x-shockwave-flash" width="100" height="100">' +
				'</embed>'
			);
		} else if ( !window.isFlashSupported() ) {
			window.LightBox.setText(
				'<img src="' + mw.config.get( 'wgExtensionAssetsPath' ) + '/SocialProfile/images/ajax-loader-white.gif" alt="" />'
			);
		} else {
			window.LightBox.setText( mw.msg( 'poll-js-loading' ) );
		}
	},

	/**
	 * Skip the current poll and pick a new, random one.
	 */
	skip: function () {
		PollNY.loadingLightBox();
		( new mw.Api() ).postWithToken( 'csrf', {
			action: 'pollny',
			format: 'json',
			what: 'vote',
			pollID: document.getElementById( 'poll_id' ).value,
			choiceID: -1
		} ).done( function () {
			PollNY.goToNewPoll();
		} );
	},

	/**
	 * Vote for a poll and move to the next poll.
	 */
	vote: function () {
		if ( PollNY.voted == 1 ) {
			return 0;
		}

		PollNY.voted = 1;

		PollNY.loadingLightBox();
		var choice_id = 0;
		for ( var i = 0; i < document.poll.poll_choice.length; i++ ) {
			if ( document.poll.poll_choice[ i ].checked ) {
				choice_id = document.poll.poll_choice[ i ].value;
			}
		}

		if ( choice_id ) {
			// cast vote
			( new mw.Api() ).postWithToken( 'csrf', {
				action: 'pollny',
				format: 'json',
				what: 'vote',
				pollID: document.getElementById( 'poll_id' ).value,
				choiceID: choice_id
			} ).done( function () {
				PollNY.goToNewPoll();
			} );
		}
	},

	/**
	 * Fetch a randomly chosen poll from the database and go to it by
	 * manipulating window.location.
	 * If there are no more polls, prompt the user to create one, unless
	 * they're on Special:CreatePoll.
	 */
	goToNewPoll: function () {
		jQuery.ajax( {
			type: 'POST',
			url: mw.util.wikiScript( 'api' ),
			data: {
				action: 'pollny',
				what: 'getRandom',
				format: 'json'
			}
		} ).done( function ( data ) {
			// redirect to next poll they haven't voted for
			if ( data.pollny.result !== 'error' ) {
				window.location = mw.config.get( 'wgServer' ) +
					mw.config.get( 'wgScriptPath' ) +
					'/index.php?title=' + data.pollny.result +
					'&prev_id=' + mw.config.get( 'wgArticleId' );
			} else {
				if (
					mw.config.get( 'wgCanonicalSpecialPageName' ) == 'CreatePoll'
				) {
					OO.ui.alert( mw.msg( 'poll-createpoll-error-nomore' ) );
				} else {
					// We have run out of polls to show
					// Show a lightbox prompting the user to create more polls
					window.LightBox.setText( mw.msg(
						'poll-finished',
						mw.util.getUrl( mw.config.get( 'wgFormattedNamespaces' )[ -1 ] + ':' + 'CreatePoll' ),
						window.location
					) );
				}
			}
		} );
	},

	/**
	 * Change the status of a poll, commit changes to the DB and reload the
	 * current page.
	 *
	 * @param status Integer: 0 = closed, 1 = open, 2 = flagged
	 */
	toggleStatus: function ( status ) {
		var msg;
		switch ( status ) {
			case 0:
				msg = mw.msg( 'poll-close-message' );
				break;
			case 1:
				msg = mw.msg( 'poll-open-message' );
				break;
			case 2:
				msg = mw.msg( 'poll-flagged-message' );
				break;
		}

		OO.ui.confirm( msg ).done( function ( confirmed ) {
			if ( confirmed ) {
				( new mw.Api() ).postWithToken( 'csrf', {
					action: 'pollny',
					format: 'json',
					what: 'updateStatus',
					pollID: document.getElementById( 'poll_id' ).value,
					status: status
				} ).done( function () {
					window.location.reload();
				} );
			}
		} );
	},

	// Embed poll stuff
	showEmbedPoll: function ( id ) {
		var loadingElement = document.getElementById( 'loading-poll_' + id ),
			displayElement = document.getElementById( 'poll-display_' + id );
		if ( loadingElement ) {
			loadingElement.style.display = 'none';
			loadingElement.style.visibility = 'hidden';
		}
		displayElement.style.display = 'block';
		displayElement.style.visibility = 'visible';
	},

	/**
	 * Cast a vote for an embedded poll.
	 *
	 * @param id Integer: poll ID number
	 * @param pageId Integer:
	 */
	pollEmbedVote: function ( id, pageId ) {
		var choice_id = 0,
			poll_form = eval( 'document.poll_' + id + '.poll_choice' );

		for ( var i = 0; i < poll_form.length; i++ ) {
			if ( poll_form[ i ].checked ) {
				choice_id = poll_form[ i ].value;
			}
		}

		if ( choice_id ) {
			// Cast vote
			( new mw.Api() ).postWithToken( 'csrf', {
				action: 'pollny',
				format: 'json',
				what: 'vote',
				pollID: id,
				choiceID: choice_id
			} ).done( function () {
				PollNY.showResults( id, pageId );
			} );
		}
	},

	/**
	 * Show the results of an embedded poll.
	 *
	 * @param id Integer: poll ID number
	 * @param pageId Integer:
	 */
	showResults: function ( id, pageId ) {
		jQuery.ajax( {
			type: 'POST',
			url: mw.util.wikiScript( 'api' ),
			data: {
				action: 'pollny',
				what: 'getPollResults',
				pageID: pageId,
				format: 'json'
			}
		} ).done( function ( data ) {
			jQuery( '#poll-display_' + id ).html( data.pollny.result );
		} );
	},

	// The next two functions are from SpecialAdminPoll.php
	/**
	 * @param id
	 * @param status
	 * @todo FIXME: would be nice if we could somehow merge this function with
	 * toggleStatus()...the major differences here are the id argument (which
	 * is present only here) and what's done after the AJAX function has been
	 * called; this function shows the text "action complete" on a given
	 * element, while toggleStatus() reloads the page
	 */
	poll_admin_status: function ( id, status ) {
		var msg;
		switch ( status ) {
			case 0:
				msg = mw.msg( 'poll-close-message' );
				break;
			case 1:
				msg = mw.msg( 'poll-open-message' );
				break;
			case 2:
				msg = mw.msg( 'poll-flagged-message' );
				break;
		}

		OO.ui.confirm( msg ).done( function ( confirmed ) {
			if ( confirmed ) {
				( new mw.Api() ).postWithToken( 'csrf', {
					action: 'pollny',
					format: 'json',
					what: 'updateStatus',
					pollID: id,
					status: status
				} ).done( function () {
					jQuery( '#poll-' + id + '-controls' ).html( mw.msg( 'poll-js-action-complete' ) );
				} );
			}
		} );
	},

	/**
	 * Delete a poll with the given ID number.
	 *
	 * @param id Integer: ID number of the poll that we're about to delete
	 */
	poll_delete: function ( id ) {
		var msg = mw.msg( 'poll-delete-message' );

		OO.ui.confirm( msg ).done( function ( confirmed ) {
			if ( confirmed ) {
				( new mw.Api() ).postWithToken( 'csrf', {
					action: 'pollny',
					format: 'json',
					what: 'delete',
					pollID: id
				} ).done( function () {
					jQuery( '#poll-' + id + '-controls' ).html( mw.msg( 'poll-js-action-complete' ) );
				} );
			}
		} );
	},

	// from Special:CreatePoll UI template
	updateAnswerBoxes: function () {
		var elem;
		for ( var x = 1; x <= 9; x++ ) {
			if ( document.getElementById( 'answer_' + x ).value ) {
				elem = document.getElementById( 'poll_answer_' + ( x + 1 ) );
				elem.style.display = 'block';
				elem.style.visibility = 'visible';
			}
		}
	},

	resetUpload: function () {
		var uploadElement = document.getElementById( 'imageUpload-frame' );
		uploadElement.src = mw.util.getUrl( mw.config.get( 'wgFormattedNamespaces' )[ -1 ] + ':' + 'PollAjaxUpload' ) + '?wpThumbWidth=75';
		uploadElement.style.display = 'block';
		uploadElement.style.visibility = 'visible';
	},

	completeImageUpload: function () {
		document.getElementById( 'poll_image' ).innerHTML =
			'<div style="margin:0px 0px 10px 0px;"><img height="75" width="75" src="' +
			mw.config.get( 'wgExtensionAssetsPath' ) + '/PollNY/images/ajax-loader-white.gif"></div>';
	},

	uploadError: function ( error ) {
		document.getElementById( 'poll_image' ).innerHTML = error + '<p>';
		PollNY.resetUpload();
	},

	/**
	 * Called after an image has been uploaded via the mini-AJAX upload form on
	 * Special:CreatePoll.
	 * This function displays the newly-uploaded image as well as the "Upload
	 * new image" link and it sets the value of the "poll_image_name" input
	 * of the form (form1).
	 * This insane logic is used by other social tools (like QuizGame, etc.)
	 * and if memory serves me correct, I wrote a lengthier explanation on one
	 * of those extension's files.
	 *
	 * @param img_tag
	 * @param img_name
	 */
	uploadComplete: function ( img_tag, img_name ) {
		jQuery( '#poll_image' ).html( img_tag );
		jQuery( '#poll_image' ).append(
			jQuery( '<a>' )
				.attr( 'href', '#' )
				.on( 'click', function () { PollNY.resetUpload(); } )
				.text( mw.msg( 'poll-upload-new-image' ) )
				// Words of wisdom:
				// <Vulpix> oh, yeah, I know what's happening. Since you're appending the element created with $('<a>'), it appends only it, not the wrapped one... You may need to add a .parent() at the end to get the <p> also...
				// (the <p> tag is a minor cosmetic improvement, nothing else)
				.wrap( '<p/>' )
				.parent()
		);
		document.form1.poll_image_name.value = img_name;
		document.getElementById( 'imageUpload-frame' ).style.display = 'none';
		document.getElementById( 'imageUpload-frame' ).style.visibility = 'hidden';
	},

	/**
	 * Create a poll.
	 *
	 * First performs some sanity checks, such as making sure that there are
	 * enough answer options, that there is a question, that the title does not
	 * contain the hash character and finally, that there isn't already a poll
	 * with the exact same title.
	 */
	create: function () {
		var answers = 0;
		for ( var x = 1; x <= 9; x++ ) {
			if ( document.getElementById( 'answer_' + x ).value ) {
				answers++;
			}
		}

		if ( answers < 2 ) {
			OO.ui.alert( mw.msg( 'poll-atleast' ) );
			return '';
		}

		var val = document.getElementById( 'poll_question' ).value;
		if ( !val ) {
			OO.ui.alert( mw.msg( 'poll-enterquestion' ) );
			return '';
		}

		if ( val.indexOf( '#' ) > -1 ) {
			OO.ui.alert( mw.msg( 'poll-hash' ) );
			return '';
		}

		// Encode ampersands
		val = val.replace( '&', '%26' );

		// Check that the title doesn't exist already; if it does, alert the
		// user about this problem; otherwise submit the form
		( new mw.Api() ).get( {
			action: 'query',
			titles: mw.config.get( 'wgFormattedNamespaces' )[ 300 ] + ':' + val,
			format: 'json',
			formatversion: 2
		} ).done( function ( data ) {
			// Missing page means that we can create it, obviously!
			if ( data.query.pages[ 0 ] && data.query.pages[ 0 ].missing === true ) {
				document.form1.submit();
			} else {
				// could also show data.query.pages[0].invalidreason to the user here instead
				OO.ui.alert( mw.msg( 'poll-pleasechoose' ) );
			}
		} );
	}
};

jQuery( function () {
	// This is assuming that NS_POLL == 300 and no-one ever touches
	// Poll.namespaces.php in order to change that...
	if ( jQuery( 'body' ).hasClass( 'ns-300' ) ) {
		// If LightBox is not yet loaded, well, load it!
		mw.loader.using( 'ext.socialprofile.LightBox', function () {
			LightBox.init();
		} );
		PollNY.show();

		jQuery( 'a.poll-status-toggle-link' ).on( 'click', function ( e ) {
			e.preventDefault();
			PollNY.toggleStatus( jQuery( this ).data( 'status' ) );
		} );

		jQuery( 'div.poll-choice input[type="radio"]' ).on( 'click', function () {
			PollNY.vote();
		} );

		jQuery( 'a.poll-skip-link' ).on( 'click', function () {
			PollNY.skip();
		} );

		jQuery( 'a.poll-next-poll-link' ).on( 'click', function ( e ) {
			e.preventDefault();
			PollNY.loadingLightBox();
			PollNY.goToNewPoll();
		} );
	}

	// Polls embedded via the <pollembed> tag
	if ( jQuery( '.poll-embed-title' ).length > 0 ) {
		// This is somewhat of a hack, because I'm lazy
		var id = jQuery( 'div.poll-loading-msg' ).attr( 'id' ),
			pollID = id.replace( /loading-poll_/, '' );
		PollNY.showEmbedPoll( pollID );

		// Handle clicks on the options
		jQuery( 'div.poll-choice input[type="radio"]' ).on( 'click', function () {
			PollNY.pollEmbedVote(
				jQuery( this ).data( 'poll-id' ),
				jQuery( this ).data( 'poll-page-id' )
			);
		} );
	}

	// Unflag/Open/Close/Delete poll links on Special:AdminPoll
	jQuery( 'a.poll-unflag-link, a.poll-open-link' ).on( 'click', function ( e ) {
		e.preventDefault();
		PollNY.poll_admin_status( jQuery( this ).data( 'poll-id' ), 1 );
	} );

	jQuery( 'a.poll-close-link' ).on( 'click', function ( e ) {
		e.preventDefault();
		PollNY.poll_admin_status( jQuery( this ).data( 'poll-id' ), 0 );
	} );

	jQuery( 'a.poll-delete-link' ).on( 'click', function ( e ) {
		e.preventDefault();
		PollNY.poll_delete( jQuery( this ).data( 'poll-id' ) );
	} );

	// Code specific to Special:CreatePoll
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'CreatePoll' ) {
		jQuery( 'div.create-poll-top input[type="button"]' ).on( 'click', function () {
			PollNY.goToNewPoll();
		} );

		// Register PollNY.updateAnswerBoxes() as the handler for elements that
		// have an ID ranging from answer_2 to answer_9
		for ( var x = 1; x <= 9; x++ ) {
			jQuery( 'input#answer_' + x ).on( 'keyup', function () {
				PollNY.updateAnswerBoxes();
			} );
			// Mobile (Android) support
			// @see https://mathiasbynens.be/notes/oninput
			// @todo FIXME: jumpy, but better than not showing the boxes 3-10 at all
			jQuery( 'input#answer_' + x ).on( 'input', function () {
				jQuery( this ).off( 'keyup' );
				PollNY.updateAnswerBoxes();
			} );
		}

		jQuery( 'input#poll-create-button' ).on( 'click', function ( e ) {
			e.preventDefault();
			PollNY.create();
		} );
	}
} );
