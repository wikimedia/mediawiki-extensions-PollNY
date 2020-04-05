<?php
/**
 * A special page to administer existing polls (i.e. examine flagged ones,
 * delete them and so on).
 * @file
 * @ingroup Extensions
 */
class AdminPoll extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AdminPoll', 'polladmin' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
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

		// If the user doesn't have the required permission, display an error
		if ( !$user->isAllowed( 'polladmin' ) ) {
			throw new PermissionsError( 'polladmin' );
		}

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( 'ext.pollNY' );

		// Pagination
		$per_page = 20;
		$page = $request->getInt( 'page', 1 );

		$current_status = $request->getVal( 'status' );
		if ( !$current_status ) {
			$current_status = 'all';
		}

		$limit = $per_page;

		$nav = [
			'all' => $this->msg( 'poll-admin-viewall' )->text(),
			'open' => $this->msg( 'poll-admin-open' )->text(),
			'closed' => $this->msg( 'poll-admin-closed' )->text(),
			'flagged' => $this->msg( 'poll-admin-flagged' )->text()
		];

		$output = '<div class="view-poll-top-links">
			<a href="javascript:history.go(-1);">' . $this->msg( 'poll-take-button' )->text() . '</a>
		</div>

		<div class="view-poll-navigation">
			<h2>' . $this->msg( 'poll-admin-status-nav' )->text() . '</h2>';

		foreach ( $nav as $status => $title ) {
			$output .= '<p>';
			if ( $current_status != $status ) {
				$output .= '<a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL( "status={$status}" ) ) . "\">{$title}</a>";
			} else {
				$output .= "<b>{$title}</b>";
			}

			$output .= '</p>';
		}

		$output .= '</div>';

		// Give grep a chance to find the usages:
		// poll-admin-title-all, poll-admin-title-closed, poll-admin-title-flagged, poll-admin-title-open
		$out->setPageTitle( $this->msg( 'poll-admin-title-' . $current_status )->text() );

		$params['ORDER BY'] = 'poll_date DESC';
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

		$dbr = wfGetDB( DB_MASTER );
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
			$where['poll_status'] = $status;
		}

		$s = $dbr->selectRow(
			'poll_question',
			[ 'COUNT(*) AS count' ],
			$where,
			__METHOD__,
			$params
		);

		$total = $s->count;

		$output .= '<div class="view-poll">';

		$x = ( ( $page - 1 ) * $per_page ) + 1;

		// If we have nothing, show an error(-ish) message, but don't return
		// because it could be that we have plenty of polls in the database,
		// but none of 'em matches the given criteria (status param in the URL)
		// For example, there are no flagged polls or closed polls. This msg
		// gets shown even then.
		if ( !$dbr->numRows( $res ) ) {
			$out->addWikiMsg( 'poll-admin-no-polls' );
		}

		foreach ( $res as $row ) {
			$creatorUser = User::newFromActorId( $row->poll_actor );
			$user_create = htmlspecialchars( $creatorUser->getName(), ENT_QUOTES );
			$avatar = new wAvatar( $creatorUser->getId(), 'm' );
			$poll_title = $row->poll_text;
			$poll_date = wfTimestamp( TS_UNIX, $row->poll_date );
			$poll_answers = $row->poll_vote_count;
			$rowId = "poll-row-{$x}";
			$title = Title::makeTitle( NS_POLL, $poll_title );

			$p = new Poll();
			$poll_choices = $p->getPollChoices( $row->poll_id );

			if ( ( $x < $dbr->numRows( $res ) ) && ( $x % $per_page != 0 ) ) {
				$output .= "<div class=\"view-poll-row\" id=\"{$rowId}\">";
			} else {
				$output .= "<div class=\"view-poll-row-bottom\" id=\"{$rowId}\">";
			}

			$poll_url = htmlspecialchars( $title->getFullURL() );
			$output .= "<div class=\"view-poll-number\">{$x}.</div>
					<div class=\"view-poll-user-image\">{$avatar->getAvatarURL()}</div>
					<div class=\"view-poll-user-name\">{$user_create}</div>
					<div class=\"view-poll-text\">
					<p><b><a href=\"{$poll_url}\">{$poll_title}</a></b></p>
					<p>";
			foreach ( $poll_choices as $choice ) {
				$output .= "{$choice['choice']}<br />";
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
						<div id=\"poll-{$row->poll_id}-controls\">";
			if ( $row->poll_status == Poll::STATUS_FLAGGED ) {
				$output .= "<a class=\"poll-unflag-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
					$this->msg( 'poll-unflag-poll' )->escaped() . '</a>';
			}
			if ( $row->poll_status == Poll::STATUS_CLOSED ) {
				$output .= " <a class=\"poll-open-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
					$this->msg( 'poll-open-poll' )->escaped() . '</a>';
			}
			if ( $row->poll_status == Poll::STATUS_OPEN ) {
				$output .= " <a class=\"poll-close-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
					$this->msg( 'poll-close-poll' )->escaped() . '</a>';
			}
			$output .= " <a class=\"poll-delete-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
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
				) . $this->msg( 'word-separator' )->plain();
			}

			if ( ( $total % $per_page ) != 0 ) {
				$numofpages++;
			}
			if ( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
			}
			if ( $numofpages >= ( $total / $per_page ) ) {
				$numofpages = ( $total / $per_page ) + 1;
			}

			for ( $i = 1; $i <= $numofpages; $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$viewPoll,
						$i,
						[],
						[
							'type' => 'most',
							'page' => $i
						]
					) . $this->msg( 'word-separator' )->plain();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->plain() .
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
				$wikipage = WikiPage::factory( $pollTitle );
				if ( version_compare( MW_VERSION, '1.35', '<' ) ) {
					$wikipage->doDeleteArticleReal( 'delete poll' );
				} else {
					// Different signature in 1.35 and above
					$wikipage->doDeleteArticleReal( 'delete poll', $user );
				}
			}
		}

		return $retVal;
	}

	protected function getGroupName() {
		return 'poll';
	}
}
