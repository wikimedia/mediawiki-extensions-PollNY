<?php

use MediaWiki\MediaWikiServices;

class PollPage extends Article {

	/**
	 * Constructor and clear the article
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		parent::__construct( $title );
	}

	/**
	 * Called on every poll page view.
	 */
	public function view() {
		global $wgExtensionAssetsPath;

		// Perform no custom handling if the poll in question has been deleted
		if ( !$this->getPage()->getId() ) {
			parent::view();
		}

		// WHAT DOES MARSELLUS WALLACE LOOK LIKE?
		$what = $this->getContext();

		$out = $what->getOutput();
		$request = $what->getRequest();
		$user = $what->getUser();
		$title = $this->getTitle();
		$lang = $what->getLanguage();

		$out->setHTMLTitle( $title->getText() );
		$out->setPageTitle( $title->getText() );

		$p = new Poll();

		// NoJS POST handler for no-JS votes
		// JS equivalent is calling ApiPollNY.php with what=vote and other appropriate params
		$pollID = $request->getInt( 'poll_id' );
		$choiceID = $request->getInt( 'poll_choice' );
		if (
			$user->isAllowed( 'pollny-vote' ) &&
			$request->wasPosted() &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
			!$p->userVoted( $user, $pollID )
		) {
			$p->addPollVote( $pollID, $choiceID, $user );
			// Maybe redirect to a random, different poll after voting (just as the JS version does)
			$randomURL = $p->getRandomPollURL( $user );
			if ( $randomURL !== 'error' ) {
				$out->redirect( Title::newFromText( $randomURL )->getFullURL( [
					'prev_id' => $this->getPage()->getId()
				] ) );
			}
			$show_results = true;
			// @todo FIXME: display a CTA of some kind in case of 'error', like in the JS function goToNewPoll()
		}

		$createPollObj = SpecialPage::getTitleFor( 'CreatePoll' );

		// Get total polls count so we can tell the user how many they have
		// voted for out of total
		$dbr = wfGetDB( DB_PRIMARY );
		$total_polls = 0;
		$s = $dbr->selectRow(
			'poll_question',
			[ 'COUNT(*) AS count' ],
			[],
			__METHOD__
		);
		if ( $s !== false ) {
			$total_polls = $lang->formatNum( $s->count );
		}

		$stats = new UserStats( $user->getId(), $user->getName() );
		$stats_current_user = $stats->getUserStats();

		$poll_info = $p->getPoll( $title->getArticleID() );

		if ( !isset( $poll_info['id'] ) ) {
			return;
		}

		$imgPath = $wgExtensionAssetsPath . '/SocialProfile/images';

		// Set up submitter data
		$creatorUser = User::newFromActorId( $poll_info['actor'] );
		$creatorUserName = $creatorUser->getName();
		$creatorUserId = $creatorUser->getId();
		$user_title = $creatorUser->getUserPage();
		$avatar = new wAvatar( $creatorUserId, 'l' );
		$stats = new UserStats( $creatorUserId, $creatorUserName );
		$stats_data = $stats->getUserStats();
		$user_name_short = htmlspecialchars( $lang->truncateForVisual( $creatorUserName, 27 ), ENT_QUOTES );
		$safeUserPageURL = htmlspecialchars( $user_title->getFullURL(), ENT_QUOTES );

		$output = '<div class="poll-right">';
		// Show the "create a poll" link to registered users
		if ( $user->isRegistered() ) {
			$output .= '<div class="create-link">
				<a href="' . htmlspecialchars( $createPollObj->getFullURL() ) . '">
					<img src="' . $imgPath . '/addIcon.gif" alt="" />'
					. wfMessage( 'poll-create' )->escaped() .
				'</a>
			</div>';
		}

		$formattedVoteCount = htmlspecialchars( $lang->formatNum( $stats_data['votes'] ), ENT_QUOTES );
		$formattedEditCount = htmlspecialchars( $lang->formatNum( $stats_data['edits'] ), ENT_QUOTES );
		$formattedCommentCount = htmlspecialchars( $lang->formatNum( $stats_data['comments'] ), ENT_QUOTES );
		$avatarImage = $avatar->getAvatarURL();

		$output .= '<div class="credit-box">
					<h1>' . wfMessage( 'poll-submitted-by' )->escaped() . "</h1>
					<div class=\"submitted-by-image\">
						<a href=\"{$safeUserPageURL}\">
							{$avatarImage}
						</a>
					</div>
					<div class=\"submitted-by-user\">
						<a href=\"{$safeUserPageURL}\">{$user_name_short}</a>
						<ul>
							<li>
								<img src=\"{$imgPath}/voteIcon.gif\" alt=\"\" />
								{$formattedVoteCount}
							</li>
							<li>
								<img src=\"{$imgPath}/editIcon.gif\" alt=\"\" />
								{$formattedEditCount}
							</li>
							<li>
								<img src=\"{$imgPath}/commentsIcon.gif\" alt=\"\" />
								{$formattedCommentCount}
							</li>
						</ul>
					</div>
					<div class=\"visualClear\"></div>

					<a href=\"" . htmlspecialchars( SpecialPage::getTitleFor( 'ViewPoll' )->getFullURL( 'user=' . $creatorUserName ) ) . '">'
						. wfMessage( 'poll-view-all-by', $user_name_short, $creatorUserName )->parse() . '</a>
				</div>';

		$output .= '<div class="poll-stats">';

		if ( $user->isRegistered() ) {
			$output .= wfMessage(
				'poll-voted-for',
				$stats_current_user['poll_votes'],
				$total_polls,
				$lang->formatNum( $stats_current_user['poll_votes'] * 5 )
			)->parse();
		} else {
			$output .= wfMessage( 'poll-would-have-earned' )
				->numParams( $total_polls * 5 )->parse();
		}

		$output .= '</div>' . "\n";

		$pollIsOpen = ( $poll_info['status'] == Poll::STATUS_OPEN );
		$userIsBlocked = $user->getBlock();

		if ( $pollIsOpen ) {
			$toggle_flag_label = wfMessage( 'poll-flag-poll' )->escaped();
			$toggle_flag_status = Poll::STATUS_FLAGGED;
			$flagAction = 'flag';
			$toggle_label = wfMessage( 'poll-close-poll' )->escaped();
			$toggle_status = Poll::STATUS_CLOSED;
			$toggleAction = 'close';
		} else {
			$toggle_flag_label = wfMessage( 'poll-unflag-poll' )->escaped();
			$toggle_flag_status = Poll::STATUS_OPEN;
			$flagAction = 'unflag';
			$toggle_label = wfMessage( 'poll-open-poll' )->escaped();
			$toggle_status = Poll::STATUS_OPEN;
			$toggleAction = 'open';
		}

		$output .= '<div class="poll-links">' . "\n";

		$adminLinks = [];
		// Poll administrators can access the poll admin panel
		if ( $user->isAllowed( 'polladmin' ) ) {
			$adminLinks[] = MediaWikiServices::getInstance()->getLinkRenderer()->makeLink(
				SpecialPage::getTitleFor( 'AdminPoll' ),
				wfMessage( 'poll-admin-panel' )->text()
			);
		}
		if ( ( $pollIsOpen && ( $poll_info['actor'] == $user->getActorId() || $user->isAllowed( 'polladmin' ) ) ) && !$userIsBlocked ) {
			$adminLinks[] = "<a class=\"poll-status-toggle-link\" href=\"javascript:void(0)\" data-status=\"{$toggle_status}\">{$toggle_label}</a>";
		}
		if ( ( $pollIsOpen || $user->isAllowed( 'polladmin' ) ) && !$userIsBlocked ) {
			$adminLinks[] = "<a class=\"poll-status-toggle-link\" href=\"javascript:void(0)\" data-status=\"{$toggle_flag_status}\">{$toggle_flag_label}</a>";
		}
		if ( !empty( $adminLinks ) ) {
			$output .= $lang->pipeList( $adminLinks );
		}
		$output .= "\n" . '</div>' . "\n"; // .poll-links

		$output .= '</div>' . "\n"; // .poll-right
		$output .= '<div class="poll">' . "\n";

		if ( $poll_info['image'] ) {
			$poll_image_width = 150;
			$poll_image = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $poll_info['image'] );
			$poll_image_tag = $poll_image_url = $width = '';
			if ( is_object( $poll_image ) ) {
				$poll_image_url = $poll_image->createThumb( $poll_image_width );
				if ( $poll_image->getWidth() >= $poll_image_width ) {
					$width = $poll_image_width;
				} else {
					$width = $poll_image->getWidth();
				}
			}
			if ( !empty( $poll_image_url ) ) {
				$poll_image_tag = '<img width="' . $width . '" alt="" src="' . $poll_image_url . '"/>';
			}
			$output .= "<div class=\"poll-image\">{$poll_image_tag}</div>";
		}

		// Display question and let user vote
		if (
			$user->isAllowed( 'pollny-vote' ) &&
			!$p->userVoted( $user, $poll_info['id'] ) &&
			$poll_info['status'] == Poll::STATUS_OPEN
		) {
			$output .= '<div id="loading-poll">' . wfMessage( 'poll-js-loading' )->escaped() . '</div>' . "\n";
			$output .= '<div id="poll-display" style="display:none;">' . "\n";
			$output .= '<form name="poll" method="post" action="">';
			$output .= '<input type="hidden" id="poll_id" name="poll_id" value="' . (int)$poll_info['id'] . '"/>' . "\n";
			$output .= Html::hidden( 'wpEditToken', $user->getEditToken() );

			foreach ( $poll_info['choices'] as $choice ) {
				$output .= '<div class="poll-choice">
					<input type="radio" name="poll_choice" id="poll_choice" value="' . (int)$choice['id'] . '" />'
						. htmlspecialchars( $choice['choice'], ENT_QUOTES ) .
				'</div>';
			}

			$output .= Html::submitButton( wfMessage( 'poll-submit-btn' )->text(), [ 'class' => 'poll-vote-btn-nojs' ] );
			$output .= '</form>
					</div>' . "\n";

			$output .= '<div class="poll-timestamp">' .
					wfMessage( 'poll-createdago', Poll::getTimeAgo( $poll_info['timestamp'] ) )->parse() .
				'</div>' . "\n";

			$output .= "\t\t\t\t\t" . '<div class="poll-button">
					<a class="poll-skip-link" href="javascript:void(0);">' .
						wfMessage( 'poll-skip' )->escaped() . '</a>
				</div>';

			if ( $request->getInt( 'prev_id' ) ) {
				$p = new Poll();
				$poll_info_prev = $p->getPoll( $request->getInt( 'prev_id' ) );
				$poll_title = Title::makeTitle( NS_POLL, $poll_info_prev['question'] );
				$output .= '<div class="previous-poll">';

				$output .= '<div class="previous-poll-title">' . wfMessage( 'poll-previous-poll' )->escaped() .
					' - <a href="' . htmlspecialchars( $poll_title->getFullURL(), ENT_QUOTES ) . '">' .
						htmlspecialchars( $poll_info_prev['question'], ENT_QUOTES ) .
					"</a></div>
					<div class=\"previous-sub-title\">"
						. wfMessage( 'poll-view-answered-times', $poll_info_prev['votes'] )->parse() .
					'</div>';

				$x = 1;

				foreach ( $poll_info_prev['choices'] as $choice ) {
					if ( $poll_info_prev['votes'] > 0 ) {
						$percent = round( (int)$choice['votes'] / (int)$poll_info_prev['votes'] * 100 );
						$bar_width = floor( 360 * ( (int)$choice['votes'] / (int)$poll_info_prev['votes'] ) );
					} else {
						$percent = 0;
						$bar_width = 0;
					}

					if ( empty( $choice['votes'] ) ) {
						$choice['votes'] = 0;
					}

					$bar_img = Html::element( 'img', [
						'src' => $imgPath . '/vote-bar-' . $x . '.gif',
						'class' => 'image-choice-' . $x,
						'style' => 'width:' . $bar_width . 'px;height:11px;'
					] );
					// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
					$safeChoice = htmlspecialchars( $choice['choice'], ENT_QUOTES );
					$output .= "<div class=\"previous-poll-choice\">
								<div class=\"previous-poll-choice-left\">{$safeChoice} ({$percent}%)</div>";

					$output .= "<div class=\"previous-poll-choice-right\">{$bar_img} <span class=\"previous-poll-choice-votes\">" .
							wfMessage( 'poll-votes', $choice['votes'] )->parse() .
						'</span></div>';

					$output .= '</div>';

					$x++;
				}
				$output .= '</div>';
			}
		} else {
			$show_results = true;
			// Display message if poll has been closed for voting
			if ( $poll_info['status'] == Poll::STATUS_CLOSED ) {
				$output .= '<div class="poll-closed">' .
					wfMessage( 'poll-closed' )->escaped() . '</div>';
			}

			// Display message if poll has been flagged
			if ( $poll_info['status'] == Poll::STATUS_FLAGGED ) {
				$output .= '<div class="poll-closed">' .
					wfMessage( 'poll-flagged' )->escaped() . '</div>';
				if ( !$user->isAllowed( 'polladmin' ) ) {
					$show_results = false;
				}
			}

			if ( $show_results ) {
				$x = 1;

				foreach ( $poll_info['choices'] as $choice ) {
					if ( $poll_info['votes'] > 0 ) {
						$percent = round( $choice['votes'] / $poll_info['votes'] * 100 );
						$bar_width = floor( 480 * ( $choice['votes'] / $poll_info['votes'] ) );
					} else {
						$percent = 0;
						$bar_width = 0;
					}

					// If it's not set, it means that no-one has voted for that
					// choice yet...it also means that we need to set it
					// manually here so that i18n displays properly
					if ( empty( $choice['votes'] ) ) {
						$choice['votes'] = 0;
					}
					$bar_img = "<img src=\"{$imgPath}/vote-bar-{$x}.gif\" class=\"image-choice-{$x}\" style=\"width:{$bar_width}px;height:12px;\"/>";

					// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
					$safeChoice = htmlspecialchars( $choice['choice'], ENT_QUOTES );
					$output .= "<div class=\"poll-choice\">
					<div class=\"poll-choice-left\">{$safeChoice} ({$percent}%)</div>";

					$output .= "<div class=\"poll-choice-right\">{$bar_img} <span class=\"poll-choice-votes\">"
						. wfMessage( 'poll-votes', $choice['votes'] )->parse() .
					'</span></div>';
					$output .= '</div>';

					$x++;
				}
			}

			// @todo FIXME: actually, does this work as intended when we've run out of polls? CHECKME!
			$nextPollLink = '';
			$randomURL = $p->getRandomPollURL( $user );
			if ( $randomURL !== 'error' ) {
				$nextPollURL = Title::newFromText( $randomURL )->getFullURL( [ 'prev_id' => $this->getPage()->getId() ] );
				$nextPollLink = '<a class="poll-next-poll-link" href="' . htmlspecialchars( $nextPollURL, ENT_QUOTES ) . '">' .
					wfMessage( 'poll-next-poll' )->escaped() . '</a>';
			}

			$output .= '<div class="poll-total-votes">(' .
				wfMessage( 'poll-based-on-votes', $poll_info['votes'] )->parse() .
			')</div>
			<div class="poll-timestamp">' .
				wfMessage( 'poll-createdago', Poll::getTimeAgo( $poll_info['timestamp'] ) )->parse() .
			'</div>


			<div class="poll-button">
				<input type="hidden" id="poll_id" name="poll_id" value="' . (int)$poll_info['id'] . '" />' .
				$nextPollLink .
			'</div>';
		}

		// "Embed this on a wiki page" feature
		$poll_embed_name = htmlspecialchars( $title->getText(), ENT_QUOTES );
		$output .= '<br />
			<table cellpadding="0" cellspacing="2" border="0">
				<tr>
					<td>
						<b>' . wfMessage( 'poll-embed' )->escaped() . "</b>
					</td>
					<td>
						<form name=\"embed_poll\">
							<input name='embed_code' style='width:300px;font-size:10px;' type='text' value='<pollembed title=\"{$poll_embed_name}\" />' onclick='javascript:document.embed_poll.embed_code.focus();document.embed_poll.embed_code.select();' readonly='readonly' />
						</form>
					</td>
				</tr>
			</table>\n";

		$output .= '</div>' . "\n"; // .poll

		$output .= '<div class="visualClear"></div>';

		$out->addHTML( $output );

		global $wgPollDisplay;
		if ( $wgPollDisplay['comments'] ) {
			$out->addWikiTextAsInterface( '<comments/>' );
		}
	}
}
