<?php
/**
 * A special page to view all available polls.
 * @file
 * @ingroup Extensions
 */
class ViewPoll extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ViewPoll' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$thisTitle = $this->getPageTitle();
		$linkRenderer = $this->getLinkRenderer();

		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( 'ext.pollNY' );

		// Page either most or newest for everyone
		$type = $request->getVal( 'type' );
		if ( !$type ) {
			$type = 'most';
		}
		// ORDER BY for SQL query
		if ( $type == 'newest' ) {
			$order = 'poll_id';
		}
		if ( $type == 'most' ) {
			$order = 'poll_vote_count';
		}

		// Display only a user's most or newest
		$user_link = '';

		// Pagination
		$per_page = 20;
		$page = $request->getInt( 'page', 1 );

		$limit = $per_page;

		$limitvalue = 0;
		if ( $limit > 0 && $page ) {
			$limitvalue = $page * $limit - ( $limit );
		}

		// Safelinks
		$random_poll_link = SpecialPage::getTitleFor( 'RandomPoll' );

		$output = '
		<div class="view-poll-top-links">
			<a href="' . htmlspecialchars( $random_poll_link->getFullURL() ) . '">' .
				$this->msg( 'poll-take-button' )->text() .
			'</a>
		</div>

		<div class="view-poll-navigation">
			<h2>' . $this->msg( 'poll-view-order' )->text() . '</h2>';

		$dbr = wfGetDB( DB_REPLICA );
		$where = [];

		$user = $request->getVal( 'user' );
		$userLink = [];
		$actor = null;
		if ( $user ) {
			$actor = User::newFromName( $user );
			if ( $actor ) {
				$where['poll_actor'] = $actor->getActorId();
			}
			$userLink['user'] = $user;
		}

		if ( $type == 'newest' ) {
			$output .= '<p>' . $linkRenderer->makeLink(
				$thisTitle,
				$this->msg( 'poll-view-popular' )->text(),
				[],
				[ 'type' => 'most' ] + $userLink
			) . '</p><p><b>' .
				$this->msg( 'poll-view-newest' )->text() . '</b></p>';
		} else {
			$output .= '<p><b>' . $this->msg( 'poll-view-popular' )->text() .
				'</b></p><p>' . $linkRenderer->makeLink(
					$thisTitle,
					$this->msg( 'poll-view-newest' )->text(),
					[],
					[ 'type' => 'newest' ] + $userLink
				) . '</p>';
		}

		$output .= '</div>';

		if ( isset( $user ) ) {
			$out->setPageTitle( $this->msg( 'poll-view-title', $user )->parse() );
		} else {
			$out->setPageTitle( $this->msg( 'viewpoll' )->text() );
		}

		$res = $dbr->select(
			[ 'poll_question', 'page' ],
			[
				'poll_actor', 'poll_date', 'poll_vote_count', 'poll_text', 'poll_page_id',
				'page_id'
			],
			$where,
			__METHOD__,
			[
				'ORDER BY' => "$order DESC",
				'LIMIT' => $limit,
				'OFFSET' => $limitvalue
			],
			[ 'page' => [ 'INNER JOIN', 'poll_page_id = page_id' ] ]
		);

		$res_total = $dbr->select(
			'poll_question',
			'COUNT(*) AS total_polls',
			( ( $user && $actor ) ? [ 'poll_actor' => $actor->getActorId() ] : [] ),
			__METHOD__
		);
		$row_total = $dbr->fetchObject( $res_total );
		$total = $row_total->total_polls;

		// If there are absolutely no polls on the database, don't bother going
		// further; no point in rendering the "Back to polls" (Special:RandomPoll)
		// link or the "Order" sidebar here either, hence why we're not outputting
		// $output here
		if ( $total == 0 ) {
			$out->addWikiMsg( 'poll-admin-no-polls' );
			return;
		}

		$output .= '<div class="view-poll">';

		$x = ( ( $page - 1 ) * $per_page ) + 1;

		foreach ( $res as $row ) {
			$creatorUser = User::newFromActorId( $row->poll_actor );
			$user_create = htmlspecialchars( $creatorUser->getName(), ENT_QUOTES );
			$avatar = new wAvatar( $creatorUser->getId(), 'm' );
			$poll_title = $row->poll_text;
			$poll_date = wfTimestamp( TS_UNIX, $row->poll_date );
			$poll_answers = $row->poll_vote_count;
			$row_id = "poll-row-{$x}";
			$title = Title::makeTitle( NS_POLL, $poll_title );

			if ( ( $x < $dbr->numRows( $res ) ) && ( $x % $per_page != 0 ) ) {
				$url = htmlspecialchars( $title->getFullURL() );
				$output .= "<div class=\"view-poll-row\" id=\"{$row_id}\" onclick=\"window.location='{$url}'\">";
			} else {
				$url = htmlspecialchars( $title->getFullURL() );
				$output .= "<div class=\"view-poll-row-bottom\" id=\"{$row_id}\" onclick=\"window.location='{$url}'\">";
			}

			$output .= "<div class=\"view-poll-number\">{$x}.</div>
					<div class=\"view-poll-user-image\">
						{$avatar->getAvatarURL()}
					</div>
					<div class=\"view-poll-user-name\">{$user_create}</div>
					<div class=\"view-poll-text\">
						<p><b><u>{$poll_title}</u></b></p>
						<p class=\"view-poll-num-answers\">" .
							$this->msg(
								'poll-view-answered-times',
								$poll_answers
							)->parse() . '</p>
						<p class="view-poll-time">(' .
							$this->msg(
								'poll-ago',
								Poll::getTimeAgo( $poll_date )
							)->parse() . ')</p>
					</div>
					<div class="visualClear"></div>
				</div>';

			$x++;
		}

		$output .= '</div>
		<div class="visualClear"></div>';

		$numofpages = $total / $per_page;

		if ( $numofpages > 1 ) {
			$output .= '<div class="view-poll-page-nav">';
			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$thisTitle,
					$this->msg( 'poll-prev' )->text(),
					[],
					[
						'type' => 'most',
						'page' => ( $page - 1 )
					] + $userLink
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
						$thisTitle,
						$i,
						[],
						[
							'type' => 'most',
							'page' => $i
						] + $userLink
					) . $this->msg( 'word-separator' )->plain();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->plain() . $linkRenderer->makeLink(
					$thisTitle,
					$i,
					[],
					[
						'type' => 'most',
						'page' => ( $page + 1 )
					] + $userLink
				);
			}
			$output .= '</div>';
		}

		$out->addHTML( $output );
	}

	protected function getGroupName() {
		return 'poll';
	}
}
