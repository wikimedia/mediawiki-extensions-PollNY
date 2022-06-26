<?php

use MediaWiki\MediaWikiServices;

/**
 * A special page to administer existing polls (i.e. examine flagged ones,
 * delete them and so on).
 *
 * @file
 * @ingroup Extensions
 */
class AdminPoll extends SpecialPage {

	public function __construct() {
		parent::__construct( 'AdminPoll' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|int|null $par Parameter passed to the page, if any
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// https://phabricator.wikimedia.org/T155405
		// Throws error message when SocialProfile extension is not installed
		if ( !class_exists( 'UserStats' ) ) {
			throw new ErrorPageError( 'poll-error-socialprofile-title', 'poll-error-socialprofile' );
		}

		// Permissions checks are done a bit further because we allow partial non-polladmin
		// access to this page to serve no-JS users
		// @todo Should we do this?
		// $this->requireLogin();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->getBlock() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( 'ext.pollNY' );

		$p = new Poll();

		$output = '';

		$action = $request->getVal( 'action' );
		$pollId = $request->getInt( 'poll_id' );
		$newStatus = $request->getInt( 'new_status' );

		if (
			in_array( $action, [ 'delete', 'open', 'close', 'unflag' ] ) &&
			( $p->doesUserOwnPoll( $user, $pollId ) || $user->isAllowed( 'polladmin' ) ) ||
			$action === 'flag'
		) {
			$output .= $this->showConfirmationForm( $action, $pollId );
			$out->addHTML( $output );
			return;
		}

		// This loop handles POST actions made against this special page, primarily/only
		// by users who have JavaScript disabled; for users with JS enabled, the JS POSTs
		// requests against the API module instead
		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			switch ( $action ) {
				case 'delete':
					if ( $user->isAllowed( 'polladmin' ) ) {
						$success = self::deletePoll( $pollId, $user );
						if ( $success ) {
							$output .= Html::successBox( $this->msg( 'poll-js-action-complete' )->escaped() );
						} else {
							$output .= Html::errorBox( $this->msg( 'error' )->escaped() );
						}
					}
					break;
				default:
				// case 'flag':
				// case 'close':
				// case 'unflag':
				// case 'open':
					// everybody can flag, creators and poll admins can close
					if (
						$newStatus == Poll::STATUS_FLAGGED ||
						$p->doesUserOwnPoll( $user, $pollId ) ||
						$user->isAllowed( 'polladmin' )
					) {
						$p->updatePollStatus( $pollId, $newStatus );
						$output .= Html::successBox( $this->msg( 'poll-js-action-complete' )->escaped() );
					} else {
						$output .= Html::errorBox( $this->msg( 'error' )->escaped() );
					}
					break;
			}
		}

		// Pagination
		$per_page = 20;
		$page = $request->getInt( 'page', 1 );

		$current_status = $request->getVal( 'status' );
		if ( !$current_status ) {
			$current_status = 'all';
		}

		$limit = $per_page;

		$nav = [
			'all' => $this->msg( 'poll-admin-viewall' )->escaped(),
			'open' => $this->msg( 'poll-admin-open' )->escaped(),
			'closed' => $this->msg( 'poll-admin-closed' )->escaped(),
			'flagged' => $this->msg( 'poll-admin-flagged' )->escaped()
		];

		$thisPage = $this->getPageTitle();

		$viewPollURL = htmlspecialchars( SpecialPage::getTitleFor( 'ViewPoll' )->getFullURL(), ENT_QUOTES );

		// @todo FIXME: This is not ideal. The second condition should be like
		// $user->isAllowed( 'createpoll' ) except we currently don't have such a user
		// right...so this basically assumes that "anons cannot create polls" right now;
		// that assumption may or may not be correct.
		if ( !$user->isAllowed( 'polladmin' ) && $user->isRegistered() ) {
			$output .= $this->msg( 'poll-admin-nonadmin-note' )->escaped();
			$output .= '<br />';
		}
		$output .= '<div class="view-poll-top-links">
			<a href="' . $viewPollURL . '">' . $this->msg( 'poll-take-button' )->escaped() . '</a>
		</div>

		<div class="view-poll-navigation">
			<h2>' . $this->msg( 'poll-admin-status-nav' )->escaped() . '</h2>';

		foreach ( $nav as $status => $title ) {
			$output .= '<p>';
			if ( $current_status != $status ) {
				$output .= '<a href="' . htmlspecialchars( $thisPage->getFullURL( "status={$status}" ) ) . "\">{$title}</a>";
			} else {
				$output .= "<b>{$title}</b>";
			}

			$output .= '</p>';
		}

		$output .= '</div>';

		// Give grep a chance to find the usages:
		// poll-admin-title-all, poll-admin-title-closed, poll-admin-title-flagged, poll-admin-title-open
		$out->setPageTitle( $this->msg( 'poll-admin-title-' . $current_status )->text() );

		$params = [];
		$params['ORDER BY'] = 'poll_date DESC';
		// @phan-suppress-next-line PhanSuspiciousValueComparison
		if ( $limit > 0 ) {
			$params['LIMIT'] = $limit;
		}
		if ( $page ) {
			$params['OFFSET'] = $page * $limit - ( $limit );
		}

		$status_int = -1;
		switch ( $current_status ) {
			case 'open':
				$status_int = 1;
				break;
			case 'closed':
				$status_int = 0;
				break;
			case 'flagged':
				$status_int = 2;
				break;
		}
		$where = [];
		if ( $status_int > -1 ) {
			$where['poll_status'] = $status_int;
		}
		// NoJS: only show non-polladmins their own polls
		if ( !$user->isAllowed( 'polladmin' ) ) {
			$where['poll_actor'] = $user->getActorId();
		}

		$dbr = wfGetDB( DB_PRIMARY );
		$res = $dbr->select(
			[ 'poll_question', 'page' ],
			[
				'poll_id', 'poll_actor', 'poll_date', 'poll_status',
				'poll_vote_count', 'poll_text', 'poll_page_id', 'page_id'
			],
			$where,
			__METHOD__,
			$params,
			[ 'page' => [ 'INNER JOIN', 'poll_page_id = page_id' ] ]
		);

		if ( $status_int > -1 ) {
			$where['poll_status'] = $status_int;
		}

		$s = $dbr->selectRow(
			'poll_question',
			[ 'COUNT(*) AS count' ],
			$where,
			__METHOD__
		);

		$total = $s->count;

		$output .= '<div class="view-poll">';

		$x = ( ( $page - 1 ) * $per_page ) + 1;

		// If we have nothing, show an error(-ish) message, but don't return
		// because it could be that we have plenty of polls in the database,
		// but none of 'em matches the given criteria (status param in the URL)
		// For example, there are no flagged polls or closed polls. This msg
		// gets shown even then.
		if ( !$res->numRows() ) {
			// @todo FIXME: should take 'status' URL param into account better, for
			// "there are no _flagged_ polls" is way different from "there are no polls
			// at all"
			$out->addWikiMsg( 'poll-admin-no-polls' );
		}

		$linkRenderer = $this->getLinkRenderer();
		foreach ( $res as $row ) {
			$creatorUser = User::newFromActorId( $row->poll_actor );
			$creatorUserPage = $linkRenderer->makeKnownLink(
				$creatorUser->getUserPage(),
				$creatorUser->getName()
			);
			$avatar = new wAvatar( $creatorUser->getId(), 'm' );
			$poll_title = $row->poll_text;
			$poll_date = wfTimestamp( TS_UNIX, $row->poll_date );
			$poll_answers = $row->poll_vote_count;
			$rowId = "poll-row-{$x}";
			$title = Title::makeTitle( NS_POLL, $poll_title );

			// cast it to an int even though it already is one, but phan doesn't know that...
			$pollId = (int)$row->poll_id;

			$poll_choices = $p->getPollChoices( $row->poll_id );

			if ( ( $x < $res->numRows() ) && ( $x % $per_page != 0 ) ) {
				$output .= "<div class=\"view-poll-row\" id=\"{$rowId}\">";
			} else {
				$output .= "<div class=\"view-poll-row-bottom\" id=\"{$rowId}\">";
			}

			$poll_url = htmlspecialchars( $title->getFullURL() );
			$safePollTitle = htmlspecialchars( $poll_title, ENT_QUOTES );
			$output .= "<div class=\"view-poll-number\">{$x}.</div>
					<div class=\"view-poll-user-image\">{$avatar->getAvatarURL()}</div>
					<div class=\"view-poll-user-name\">{$creatorUserPage}</div>
					<div class=\"view-poll-text\">
					<a href=\"{$poll_url}\">{$safePollTitle}</a>
					<p>";
			foreach ( $poll_choices as $choice ) {
				$output .= htmlspecialchars( $choice['choice'], ENT_QUOTES ) . '<br />';
			}
			$output .= '</p>
						<p class="view-poll-num-answers">' .
							$this->msg(
								'poll-view-answered-times',
								$poll_answers
							)->parse() . '</p>
						<p class="view-poll-time">(' .
							$this->msg(
								'poll-ago',
								Poll::getTimeAgo( $poll_date )
							)->parse() . ")</p>
						<div id=\"poll-{$pollId}-controls\">";
			if ( $row->poll_status == Poll::STATUS_FLAGGED ) {
				$unflagLink = htmlspecialchars(
					$thisPage->getFullURL( [
						'action' => 'unflag',
						'poll_id' => $row->poll_id
					] ),
					ENT_QUOTES
				);
				$output .= "<a class=\"poll-unflag-link\" href=\"{$unflagLink}\" data-poll-id=\"{$pollId}\">" .
					$this->msg( 'poll-unflag-poll' )->escaped() . '</a>';
			}
			if ( $row->poll_status == Poll::STATUS_CLOSED ) {
				$openLink = htmlspecialchars(
					$thisPage->getFullURL( [
						'action' => 'open',
						'poll_id' => $row->poll_id
					] ),
					ENT_QUOTES
				);
				$output .= " <a class=\"poll-open-link\" href=\"{$openLink}\" data-poll-id=\"{$pollId}\">" .
					$this->msg( 'poll-open-poll' )->escaped() . '</a>';
			}
			if ( $row->poll_status == Poll::STATUS_OPEN ) {
				$closeLink = htmlspecialchars(
					$thisPage->getFullURL( [
						'action' => 'close',
						'poll_id' => $row->poll_id
					] ),
					ENT_QUOTES
				);
				$output .= " <a class=\"poll-close-link\" href=\"{$closeLink}\" data-poll-id=\"{$pollId}\">" .
					$this->msg( 'poll-close-poll' )->escaped() . '</a>';
			}

			$deleteLink = htmlspecialchars(
				$thisPage->getFullURL( [
					'action' => 'delete',
					'poll_id' => $row->poll_id
				] ),
				ENT_QUOTES
			);
			$output .= " <a class=\"poll-delete-link\" href=\"{$deleteLink}\" data-poll-id=\"{$pollId}\">" .
				$this->msg( 'poll-delete-poll' )->escaped() . '</a>
						</div>
					</div>
					<div class="visualClear"></div>
				</div>';

			$x++;
		}

		$output .= '</div>
		<div class="visualClear"></div>';

		$output .= $this->buildPagination( $total, $per_page, $page );

		$out->addHTML( $output );
	}

	/**
	 * Build the pagination links.
	 *
	 * @param int $total Amount of all polls in the database
	 * @param int $perPage How many items to show per page? This is
	 *                          hardcoded to 20 earlier in this file
	 * @param int $page Number indicating on which page we are
	 * @return string HTML
	 */
	public function buildPagination( $total, $perPage, $page ) {
		$output = '';
		$numofpages = $total / $perPage;
		$viewPoll = SpecialPage::getTitleFor( 'ViewPoll' );
		$linkRenderer = $this->getLinkRenderer();

		if ( $numofpages > 1 ) {
			$output .= '<div class="view-poll-page-nav">';

			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$viewPoll,
					$this->msg( 'poll-prev' )->text(),
					[],
					[
						'type' => 'most',
						'page' => ( $page - 1 )
					]
				) . $this->msg( 'word-separator' )->escaped();
			}

			if ( ( $total % $perPage ) != 0 ) {
				$numofpages++;
			}
			if ( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
			}
			if ( $numofpages >= ( $total / $perPage ) ) {
				$numofpages = ( $total / $perPage ) + 1;
			}

			for ( $i = 1; $i <= $numofpages; $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$viewPoll,
						(string)$i,
						[],
						[
							'type' => 'most',
							'page' => $i
						]
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $total - ( $perPage * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->escaped() .
					$linkRenderer->makeLink(
						$viewPoll,
						$this->msg( 'poll-next' )->text(),
						[],
						[
							'type' => 'most',
							'page' => ( $page + 1 )
						]
					);
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Delete a poll from the database and delete the associated Poll: page as well.
	 *
	 * @param int $pollID ID number of the poll to delete
	 * @param User $user The user (object) performing the deletion
	 * @return bool True if the entries were deleted, otherwise false
	 */
	public static function deletePoll( $pollID, User $user ) {
		$retVal = false;

		if ( $pollID > 0 ) {
			$dbw = wfGetDB( DB_PRIMARY );
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
				if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
					// MW 1.36+
					$wikipage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $pollTitle );
				} else {
					$wikipage = WikiPage::factory( $pollTitle );
				}
				$wikipage->doDeleteArticleReal( 'delete poll', $user );
			}
		}

		return $retVal;
	}

	/**
	 * Render the confirmation form for confirming an action.
	 *
	 * Mainly used as the no-JS fallback; for users with JavaScript enabled,
	 * the JS handles the anti-CSRF stuff and does everything somewhat more
	 * smoothly.
	 *
	 * @suppress PhanPossiblyUndeclaredVariable
	 *
	 * @param string $action close, open, flag, unflag or delete
	 * @param int $id ID of the poll that $action is going to impact
	 * @return string HTML
	 */
	private function showConfirmationForm( $action, $id ) {
		$form = '';
		$user = $this->getUser();
		$newStatus = $this->getRequest()->getInt( 'new_status' );

		$form .= '<form method="post" name="poll-action-confirm" action="">';
		switch ( $action ) {
			case 'close':
				$msgKey = 'poll-close-message';
				break;
			case 'delete':
				$msgKey = 'poll-delete-message';
				break;
			case 'flag':
				$msgKey = 'poll-flagged-message';
				break;
			case 'open':
				$msgKey = 'poll-open-message';
				break;
			case 'unflag':
				$msgKey = 'poll-unflag-message';
				break;
			default:
				break;
		}
		// Could pass in $id as ->numParams() but that'd be useless as the messages
		// don't currently use it
		$form .= $this->msg( $msgKey )->parseAsBlock();
		$form .= '<br />';
		// @todo FIXME: render the poll here when $action === 'flag' (and/or some other actions?)

		$form .= Html::hidden( 'wpEditToken', $user->getEditToken() );
		$form .= Html::hidden( 'poll_id', $id );
		$form .= Html::hidden( 'action', $action );
		if ( $newStatus ) {
			$form .= Html::hidden( 'new_status', $newStatus );
		}
		$form .= Html::submitButton( $this->msg( 'poll-submit-btn' )->text(), [ 'name' => 'wpSubmit', 'class' => 'site-button' ] );
		$form .= '</form>';

		return $form;
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'poll';
	}
}
