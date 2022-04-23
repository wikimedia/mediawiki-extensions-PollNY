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
	 * @var bool Should a CSRF token be required for the requested action to be
	 *  permitted? True when 'what' is one of the following: delete, updateStatus, vote
	 * @note Needs to be public for the API hook handler (a.k.a dumb hack) in the hooks file.
	 */
	public $shouldRequireToken;

	/**
	 * Main entry point.
	 *
	 * @suppress PhanImpossibleTypeComparison
	 * @suppress PhanPossiblyUndeclaredVariable
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

		$this->shouldRequireToken = in_array( $action, [ 'delete', 'updateStatus', 'vote' ] );

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
			// Don't...0 is a valid status code for us.
			if ( /*!$status ||*/ $status === null || !is_numeric( $status ) ) {
				$this->dieWithError( [ 'apierror-missingparam', 'status' ], 'missingparam' );
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
	}

	function delete( $pollID ) {
		$user = $this->getUser();
		if ( !$user->isAllowed( 'polladmin' ) ) {
			return '';
		}

		AdminPoll::deletePoll( $pollID, $user );

		return 'OK';
	}

	/**
	 * @param int $pageID Poll ID
	 * @return string HTML
	 */
	function getPollResults( $pageID ) {
		$assetsPath = $this->getConfig()->get( 'ExtensionAssetsPath' );

		$poll_info = $this->poll->getPoll( $pageID );
		$x = 1;

		$output = '';
		// commented-out experimental, currently unused, more programmatical
		// way of returning the desired data so that HTML could be assembled
		// client-side via JS instead of the API having to return HTML
		// $retVal = [];
		foreach ( $poll_info['choices'] as $choice ) {
			$bar_img = "<img src=\"{$assetsPath}/SocialProfile/images/vote-bar-{$x}.gif\" border=\"0\" class=\"image-choice-{$x}\" style=\"width:{$choice['percent']}%;height:12px;\"/>";

			$output .= "<div class=\"poll-choice\">
		<div class=\"poll-choice-left\">{$choice['choice']} ({$choice['percent']}%)</div>";

			$output .= "<div class=\"poll-choice-right\">{$bar_img} <span class=\"poll-choice-votes\">" .
				$this->msg( 'poll-votes', $choice['votes'] )->parse() .
				'</span></div>';
			$output .= '</div>';

			/*
			$retVal[$x] = [
				'x' => $x,
				'percent' => $choice['percent'],
				'choice' => $choice['choice'],
				'votes' => $choice['votes']
			];
			*/
			$x++;
		}

		// return $retVal;
		return $output;
	}

	function updateStatus( $pollID, $status ) {
		$user = $this->getUser();
		if (
			$status == 2 && !$user->getBlock() ||
			$this->poll->doesUserOwnPoll( $user, $pollID ) ||
			$user->isAllowed( 'polladmin' )
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

	public function needsToken() {
		// @note This...quite doesn't work as intended?
		// @see PollNY.hooks.php, function onAPIGetAllowedParams()
		if ( $this->shouldRequireToken ) {
			return 'csrf';
		}
		return false;
	}

	public function isWriteMode() {
		if ( $this->shouldRequireToken ) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=pollny&what=delete&pollID=66'
				=> 'apihelp-pollny-example-1',
			'action=pollny&what=getPollResults&pollID=666'
				=> 'apihelp-pollny-example-2',
			'action=pollny&what=getRandom'
				=> 'apihelp-pollny-example-3',
			'action=pollny&what=updateStatus&pollID=47&status=1'
				=> 'apihelp-pollny-example-5',
			'action=pollny&what=vote&pollID=33&choiceID=4'
				=> 'apihelp-pollny-example-6'
		];
	}
}
