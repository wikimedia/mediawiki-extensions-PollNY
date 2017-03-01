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
		global $wgUploadPath;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// https://phabricator.wikimedia.org/T155405
		// Throws error message when SocialProfile extension is not installed
		if( !class_exists( 'UserStats' ) ) {
			throw new ErrorPageError( 'poll-error-socialprofile-title', 'poll-error-socialprofile' );
		}

		// If the user doesn't have the required permission, display an error
		if( !$user->isAllowed( 'polladmin' ) ) {
			throw new PermissionsError( 'polladmin' );
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$out->readOnlyPage();
			return;
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( 'ext.pollNY' );

		// Pagination
		$per_page = 20;
		$page = $request->getInt( 'page', 1 );

		$current_status = $request->getVal( 'status' );
		if( !$current_status ) {
			$current_status = 'all';
		}

		$limit = $per_page;

		$nav = array(
			'all' => $this->msg( 'poll-admin-viewall' )->text(),
			'open' => $this->msg( 'poll-admin-open' )->text(),
			'closed' => $this->msg( 'poll-admin-closed' )->text(),
			'flagged' => $this->msg( 'poll-admin-flagged' )->text()
		);

		$output = '<div class="view-poll-top-links">
			<a href="javascript:history.go(-1);">' . $this->msg( 'poll-take-button' )->text() . '</a>
		</div>

		<div class="view-poll-navigation">
			<h2>' . $this->msg( 'poll-admin-status-nav' )->text() . '</h2>';

		foreach( $nav as $status => $title ) {
			$output .= '<p>';
			if( $current_status != $status ) {
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
		if( $limit > 0 ) {
			$params['LIMIT'] = $limit;
		}
		if( $page ) {
			$params['OFFSET'] = $page * $limit - ( $limit );
		}

		$status_int = -1;
		switch( $current_status ) {
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
		$where = array();
		if( $status_int > -1 ) {
			$where['poll_status'] = $status_int;
		}

		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			array( 'poll_question', 'page' ),
			array(
				'poll_id', 'poll_user_id', 'poll_date', 'poll_status',
				'poll_vote_count', 'poll_user_name', 'poll_text',
				'poll_page_id', 'page_id'
			),
			$where,
			__METHOD__,
			$params,
			array( 'page' => array( 'INNER JOIN', 'poll_page_id = page_id' ) )
		);

		if( $status_int > -1 ) {
			$where['poll_status'] = $status;
		}

		$s = $dbr->selectRow(
			'poll_question',
			array( 'COUNT(*) AS count' ),
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
			$user_create = $row->poll_user_name;
			$user_id = $row->poll_user_id;
			$avatar = new wAvatar( $user_id, 'm' );
			$poll_title = $row->poll_text;
			$poll_date = wfTimestamp( TS_UNIX, $row->poll_date );
			$poll_answers = $row->poll_vote_count;
			$rowId = "poll-row-{$x}";
			$title = Title::makeTitle( NS_POLL, $poll_title );

			$p = new Poll();
			$poll_choices = $p->getPollChoices( $row->poll_id );

			if( ( $x < $dbr->numRows( $res ) ) && ( $x % $per_page != 0 ) ) {
				$output .= "<div class=\"view-poll-row\" id=\"{$rowId}\">";
			} else {
				$output .= "<div class=\"view-poll-row-bottom\" id=\"{$rowId}\">";
			}

			$poll_url = htmlspecialchars( $title->getFullURL() );
			$output .= "<div class=\"view-poll-number\">{$x}.</div>
					<div class=\"view-poll-user-image\"><img src=\"{$wgUploadPath}/avatars/{$avatar->getAvatarImage()}\" alt=\"\" /></div>
					<div class=\"view-poll-user-name\">{$user_create}</div>
					<div class=\"view-poll-text\">
					<p><b><a href=\"{$poll_url}\">{$poll_title}</a></b></p>
					<p>";
			foreach( $poll_choices as $choice ) {
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
			if( $row->poll_status == 2 ) {
				$output .= "<a class=\"poll-unflag-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
					$this->msg( 'poll-unflag-poll' )->text() . '</a>';
			}
			if( $row->poll_status == 0 ) {
				$output .= " <a class=\"poll-open-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
					$this->msg( 'poll-open-poll' )->text() . '</a>';
			}
			if( $row->poll_status == 1 ) {
				$output .= " <a class=\"poll-close-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
					$this->msg( 'poll-close-poll' )->text() . '</a>';
			}
			$output .= " <a class=\"poll-delete-link\" href=\"javascript:void(0)\" data-poll-id=\"{$row->poll_id}\">" .
				$this->msg( 'poll-delete-poll' )->text() . '</a>
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
	 * @param $total Integer: amount of all polls in the database
	 * @param $perPage Integer: how many items to show per page? This is
	 *                          hardcoded to 20 earlier in this file
	 * @param $page Integer: number indicating on which page we are
	 * @return String: HTML
	 */
	public function buildPagination( $total, $perPage, $page ) {
		$output = '';
		$numofpages = $total / $perPage;
		$viewPoll = SpecialPage::getTitleFor( 'ViewPoll' );
		$linkRenderer = $this->getLinkRenderer();

		if( $numofpages > 1 ) {
			$output .= '<div class="view-poll-page-nav">';

			if( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$viewPoll,
					$this->msg( 'poll-prev' )->text(),
					array(),
					array(
						'type' => 'most',
						'page' => ( $page - 1 )
					)
				) . $this->msg( 'word-separator' )->plain();
			}

			if( ( $total % $per_page ) != 0 ) {
				$numofpages++;
			}
			if( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
			}
			if( $numofpages >= ( $total / $per_page ) ) {
				$numofpages = ( $total / $per_page ) + 1;
			}

			for( $i = 1; $i <= $numofpages; $i++ ) {
				if( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$viewPoll,
						$i,
						array(),
						array(
							'type' => 'most',
							'page' => $i
						)
					) . $this->msg( 'word-separator' )->plain();
				}
			}

			if( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->plain() .
					$linkRenderer->makeLink(
						$viewPoll,
						$this->msg( 'poll-next' )->text(),
						array(),
						array(
							'type' => 'most',
							'page' => ( $page + 1 )
						)
					);
			}

			$output .= '</div>';
		}

		return $output;
	}

	protected function getGroupName() {
		return 'poll';
	}
}
