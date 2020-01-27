<?php
/**
 * PollNY API module
 *
 * @file
 * @ingroup API
 * @see https://www.mediawiki.org/wiki/API:Extensions#ApiSampleApiExtension.php
 */
class ApiPollNY extends ApiBase {

	/**
	 * @var Poll Instance of the Poll class, set in execute() below
	 */
	private $poll;

	/**
	 * Main entry point.
	 */
	public function execute() {
		$user = $this->getUser();

		// Get the request parameters
		$params = $this->extractRequestParams();

		$action = $params['what'];

		// If the "what" param isn't present, we don't know what to do!
		if ( !$action || $action === null ) {
			$this->dieWithError( [ 'apierror-missingparam', 'what' ], 'missingparam' );
		}

		$pollID = $params['pollID'];
		// Ensure that the pollID parameter is present for actions that require
		// it and that it really is numeric
		if (
			in_array( $action, [ 'delete', 'updateStatus', 'vote' ] ) &&
			( !$pollID || $pollID === null || !is_numeric( $pollID ) )
		) {
			$this->dieWithError( [ 'apierror-missingparam', 'pollID' ], 'missingparam' );
		}

		// Action-specific parameter validation stuff
		if ( $action == 'getPollResults' ) {
			$pageID = $params['pageID'];
			if ( !$pageID || $pageID === null || !is_numeric( $pageID ) ) {
				$this->dieWithError( [ 'apierror-missingparam', 'pageID' ], 'missingparam' );
			}
		} elseif ( $action == 'updateStatus' ) {
			$status = $params['status'];
			if ( !$status || $status === null || !is_numeric( $status ) ) {
				$this->dieWithError( [ 'apierror-missingparam', 'status' ], 'missingparam' );
			}
		} elseif ( $action == 'titleExists' ) {
			if ( !$params['pageName'] || $params['pageName'] === null ) {
				$this->dieWithError( [ 'apierror-missingparam', 'pageName' ], 'missingparam' );
			}
		} elseif ( $action == 'vote' ) {
			$choiceID = $params['choiceID'];
			if ( !$choiceID || $choiceID === null || !is_numeric( $choiceID ) ) {
				$this->dieWithError( [ 'apierror-missingparam', 'choiceID' ], 'missingparam' );
			}
		}

		// Set the private class member variable
		$this->poll = new Poll();

		// Decide what function to call
		switch ( $action ) {
			case 'delete':
				$output = $this->delete( $pollID );
				break;
			case 'getPollResults':
				$output = $this->getPollResults( $pageID );
				break;
			case 'getRandom':
				$output = $this->poll->getRandomPollURL( $user );
				break;
			case 'updateStatus':
				$output = $this->updateStatus( $pollID, $params['status'] );
				break;
			case 'titleExists':
				$output = $this->titleExists( $params['pageName'] );
				break;
			case 'vote':
				$output = $this->vote( $pollID, (int)$params['choiceID'] );
				break;
			default:
				break;
		}

		// Top level
		$this->getResult()->addValue( null, $this->getModuleName(),
			[ 'result' => $output ]
		);

		return true;
	}

	function delete( $pollID ) {
		if ( !$this->getUser()->isAllowed( 'polladmin' ) ) {
			return '';
		}

		if ( $pollID > 0 ) {
			$dbw = wfGetDB( DB_MASTER );
			$s = $dbw->selectRow(
				'poll_question',
				[ 'poll_page_id' ],
				[ 'poll_id' => intval( $pollID ) ],
				__METHOD__
			);

			if ( $s !== false ) {
				$dbw->delete(
					'poll_user_vote',
					[ 'pv_poll_id' => intval( $pollID ) ],
					__METHOD__
				);

				$dbw->delete(
					'poll_choice',
					[ 'pc_poll_id' => intval( $pollID ) ],
					__METHOD__
				);

				$dbw->delete(
					'poll_question',
					[ 'poll_page_id' => $s->poll_page_id ],
					__METHOD__
				);

				$pollTitle = Title::newFromId( $s->poll_page_id );
				$article = new Article( $pollTitle );
				$article->doDeleteArticle( 'delete poll' );
			}
		}

		return 'OK';
	}

	function getPollResults( $pageID ) {
		global $wgExtensionAssetsPath;

		$poll_info = $this->poll->getPoll( $pageID );
		$x = 1;

		$output = '';
		foreach ( $poll_info['choices'] as $choice ) {
			// $percent = round( $choice['votes'] / $poll_info['votes'] * 100 );
			if ( $poll_info['votes'] > 0 ) {
				$bar_width = floor( 480 * ( $choice['votes'] / $poll_info['votes'] ) );
			}
			$bar_img = "<img src=\"{$wgExtensionAssetsPath}/SocialProfile/images/vote-bar-{$x}.gif\" border=\"0\" class=\"image-choice-{$x}\" style=\"width:{$choice['percent']}%;height:12px;\"/>";

			$output .= "<div class=\"poll-choice\">
		<div class=\"poll-choice-left\">{$choice['choice']} ({$choice['percent']}%)</div>";

			$output .= "<div class=\"poll-choice-right\">{$bar_img} <span class=\"poll-choice-votes\">" .
				wfMessage( 'poll-votes', $choice['votes'] )->parse() .
				'</span></div>';
			$output .= '</div>';

			$x++;
		}

		return $output;
	}

	function titleExists( $pageName ) {
		// Construct page title object to convert to database key
		$pageTitle = Title::makeTitle( NS_MAIN, urldecode( $pageName ) );
		$dbKey = $pageTitle->getDBKey();

		// Database key would be in page_title if the page already exists
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'page',
			[ 'page_id' ],
			[ 'page_title' => $dbKey, 'page_namespace' => NS_POLL ],
			__METHOD__
		);
		if ( $s !== false ) {
			return 'Page exists';
		} else {
			return 'OK';
		}
	}

	function updateStatus( $pollID, $status ) {
		if (
			$status == 2 ||
			$this->poll->doesUserOwnPoll( $this->getUser(), $pollID ) ||
			$this->getUser()->isAllowed( 'polladmin' )
		) {
			$this->poll->updatePollStatus( $pollID, $status );
			return 'Status successfully changed';
		} else {
			return 'error';
		}
	}

	function vote( $pollID, $choiceID ) {
		$user = $this->getUser();
		if ( !$user->isAllowed( 'pollny-vote' ) ) {
			return 'error';
		}
		if (
			!$this->poll->userVoted( $user, $pollID ) &&
			$user->isAllowed( 'pollny-vote' )
		) {
			$this->poll->addPollVote( $pollID, $choiceID, $user );
		}

		return 'OK';
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getDescription() {
		return 'PollNY API - includes both user and admin functions';
	}

	/**
	 * @return Array
	 */
	public function getAllowedParams() {
		return [
			'what' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'choiceID' => [
				ApiBase::PARAM_TYPE => 'integer',
			],
			'pageName' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'pollID' => [
				ApiBase::PARAM_TYPE => 'integer',
			],
			'pageID' => [
				ApiBase::PARAM_TYPE => 'integer',
			],
			'status' => [
				ApiBase::PARAM_TYPE => 'integer',
			]
		];
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), [
			'what' => 'What to do?',
			'choiceID' => 'Same as clicking the <choiceID>th choice via the GUI; only used when what=vote',
			'pageName' => 'Title to check for (only used when what=titleExists); should be URL-encoded',
			'pollID' => 'Poll ID of the poll that is being deleted/updated/voted for',
			'pageID' => 'Page ID (only used when what=getPollResults)',
			'status' => 'New status of the poll (when what=updateStatus); possible values are 0 (=closed), 1 and 2 (=flagged)',
		] );
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getExamples() {
		return [
			'api.php?action=pollny&what=delete&pollID=66' => 'Deletes the poll #66',
			'api.php?action=pollny&what=getPollResults&pollID=666' => 'Gets the results of the poll #666',
			'api.php?action=pollny&what=getRandom' => 'Gets a random poll to which the current user hasn\'t answered yet',
			'api.php?action=pollny&what=titleExists&pageName=Is%20PollNY%20awesome%3F' => 'Checks if there is already a poll with the title "Is PollNY awesome?"',
			'api.php?action=pollny&what=updateStatus&pollID=47&status=1' => 'Sets the status of the poll #47 to 1 (=open); possible status values are 0 (=closed), 1 and 2 (=flagged)',
			'api.php?action=pollny&what=vote&pollID=33&choiceID=4' => 'Votes (answers) the poll #33 with the 4th choice',
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		return [
			'action=pollny&what=delete&pollID=66'
				=> 'apihelp-pollny-example-1',
			'action=pollny&what=getPollResults&pollID=666'
				=> 'apihelp-pollny-example-2',
			'action=pollny&what=getRandom'
				=> 'apihelp-pollny-example-3',
			'action=pollny&what=titleExists&pageName=Is%20PollNY%20awesome%3F'
				=> 'apihelp-pollny-example-4',
			'action=pollny&what=updateStatus&pollID=47&status=1'
				=> 'apihelp-pollny-example-5',
			'action=pollny&what=vote&pollID=33&choiceID=4'
				=> 'apihelp-pollny-example-6'
		];
	}
}
