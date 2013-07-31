<?php

class PollPage extends Article {

	var $title = null;

	/**
	 * Constructor and clear the article
	 * @param $title Object: reference to a Title object.
	 */
	public function __construct( Title $title ) {
		parent::__construct( $title );
	}

	/**
	 * Called on every poll page view.
	 */
	public function view() {
		global $wgUser, $wgOut, $wgRequest, $wgExtensionAssetsPath, $wgUploadPath;
		global $wgSupressPageTitle, $wgNameSpacesWithEditMenu;

		// Perform no custom handling if the poll in question has been deleted
		if ( !$this->getID() ) {
			parent::view();
		}

		$wgSupressPageTitle = true;

		// WHAT DOES MARSELLUS WALLACE LOOK LIKE?
		$what = $this->getContext();

		$title = $this->getTitle();
		$lang = $what->getLanguage();

		$wgOut->setHTMLTitle( $title->getText() );
		$wgOut->setPageTitle( $title->getText() );

		$wgNameSpacesWithEditMenu[] = NS_POLL;

		$createPollObj = SpecialPage::getTitleFor( 'CreatePoll' );

		// Get total polls count so we can tell the user how many they have
		// voted for out of total
		$dbr = wfGetDB( DB_MASTER );
		$total_polls = 0;
		$s = $dbr->selectRow(
			'poll_question',
			array( 'COUNT(*) AS count' ),
			array(),
			__METHOD__
		);
		if ( $s !== false ) {
			$total_polls = $lang->formatNum( $s->count );
		}

		$stats = new UserStats( $wgUser->getID(), $wgUser->getName() );
		$stats_current_user = $stats->getUserStats();

		$p = new Poll();
		$poll_info = $p->getPoll( $title->getArticleID() );

		if( !isset( $poll_info['id'] ) ) {
			return '';
		}

		// Set up submitter data
		$user_title = Title::makeTitle( NS_USER, $poll_info['user_name'] );
		$avatar = new wAvatar( $poll_info['user_id'], 'l' );
		$avatarID = $avatar->getAvatarImage();
		$stats = new UserStats( $poll_info['user_id'], $poll_info['user_name'] );
		$stats_data = $stats->getUserStats();
		$user_name_short = $lang->truncate( $poll_info['user_name'], 27 );

		$output = '<div class="poll-right">';
		// Show the "create a poll" link to registered users
		if( $wgUser->isLoggedIn() ) {
			$output .= '<div class="create-link">
				<a href="' . $createPollObj->escapeFullURL() . '">
					<img src="' . $wgExtensionAssetsPath . '/PollNY/images/addIcon.gif" alt="" />'
					. wfMessage( 'poll-create' )->text() .
				'</a>
			</div>';
		}
		$output .= '<div class="credit-box">
					<h1>' . wfMessage( 'poll-submitted-by' )->text() . "</h1>
					<div class=\"submitted-by-image\">
						<a href=\"{$user_title->getFullURL()}\">
							<img src=\"{$wgUploadPath}/avatars/{$avatarID}\" style=\"border:1px solid #d7dee8; width:50px; height:50px;\"/>
						</a>
					</div>
					<div class=\"submitted-by-user\">
						<a href=\"{$user_title->getFullURL()}\">{$user_name_short}</a>
						<ul>
							<li>
								<img src=\"{$wgExtensionAssetsPath}/PollNY/images/voteIcon.gif\" alt=\"\" />
								{$stats_data['votes']}
							</li>
							<li>
								<img src=\"{$wgExtensionAssetsPath}/PollNY/images/pencilIcon.gif\" alt=\"\" />
								{$stats_data['edits']}
							</li>
							<li>
								<img src=\"{$wgExtensionAssetsPath}/PollNY/images/commentsIcon.gif\" alt=\"\" />
								{$stats_data['comments']}
							</li>
						</ul>
					</div>
					<div class=\"cleared\"></div>

					<a href=\"" . SpecialPage::getTitleFor( 'ViewPoll' )->escapeFullURL( 'user=' . $poll_info['user_name'] ) . '">'
						. wfMessage( 'poll-view-all-by', $user_name_short )->parse() . '</a>

				</div>';

		$output .= '<div class="poll-stats">';

		if( $wgUser->isLoggedIn() ) {
			$output .= wfMessage(
				'poll-voted-for',
				'<b>' . $stats_current_user['poll_votes'] . '</b>',
				$total_polls,
				$lang->formatNum( $stats_current_user['poll_votes'] * 5 )
			)->parse();
		} else {
			$output .= wfMessage( 'poll-would-have-earned' )
				->numParams( $total_polls * 5 )->parse();
		}

		$output .= '</div>' . "\n";

		$toggle_flag_label = ( ( $poll_info['status'] == 1 ) ? wfMessage( 'poll-flag-poll' )->text() : wfMessage( 'poll-unflag-poll' )->text() );
		$toggle_flag_status = ( ( $poll_info['status'] == 1 ) ? 2 : 1 );

		if( $poll_info['status'] == 1 ) {
			// Creator and admins can change the status of a poll
			$toggle_label = ( ( $poll_info['status'] == 1 ) ? wfMessage( 'poll-close-poll' )->text() : wfMessage( 'poll-open-poll' )->text() );
			$toggle_status = ( ( $poll_info['status'] == 1 ) ? 0 : 1 );
		}

		$output .= '<div class="poll-links">' . "\n";

		$adminLinks = array();
		// Poll administrators can access the poll admin panel
		if( $wgUser->isAllowed( 'polladmin' ) ) {
			$adminLinks[] = Linker::link(
				SpecialPage::getTitleFor( 'AdminPoll' ),
				wfMessage( 'poll-admin-panel' )->text()
			);
		}
		if( $poll_info['status'] == 1 && ( $poll_info['user_id'] == $wgUser->getID() || $wgUser->isAllowed( 'polladmin' ) ) ) {
			$adminLinks[] = "<a class=\"poll-status-toggle-link\" href=\"javascript:void(0)\" data-status=\"{$toggle_status}\">{$toggle_label}</a>";
		}
		if( $poll_info['status'] == 1 || $wgUser->isAllowed( 'polladmin' ) ) {
			$adminLinks[] = "<a class=\"poll-status-toggle-link\" href=\"javascript:void(0)\" data-status=\"{$toggle_flag_status}\">{$toggle_flag_label}</a>";
		}
		if ( !empty( $adminLinks ) ) {
			$output .= $lang->pipeList( $adminLinks );
		}
		$output .= "\n" . '</div>' . "\n"; // .poll-links

		$output .= '</div>' . "\n"; // .poll-right
		$output .= '<div class="poll">' . "\n";

		$output .= "<h1 class=\"pagetitle\">{$title->getText()}</h1>\n";

		if( $poll_info['image'] ) {
			$poll_image_width = 150;
			$poll_image = wfFindFile( $poll_info['image'] );
			$poll_image_url = $width = '';
			if ( is_object( $poll_image ) ) {
				$poll_image_url = $poll_image->createThumb( $poll_image_width );
				if ( $poll_image->getWidth() >= $poll_image_width ) {
					$width = $poll_image_width;
				} else {
					$width = $poll_image->getWidth();
				}
			}
			$poll_image_tag = '<img width="' . $width . '" alt="" src="' . $poll_image_url . '"/>';
			$output .= "<div class=\"poll-image\">{$poll_image_tag}</div>";
		}

		// Display question and let user vote
		if( !$p->userVoted( $wgUser->getName(), $poll_info['id'] ) && $poll_info['status'] == 1 ) {
			$output .= '<div id="loading-poll">' . wfMessage( 'poll-js-loading' )->text() . '</div>' . "\n";
			$output .= '<div id="poll-display" style="display:none;">' . "\n";
			$output .= '<form name="poll"><input type="hidden" id="poll_id" name="poll_id" value="' . $poll_info['id'] . '"/>' . "\n";

			foreach( $poll_info['choices'] as $choice ) {
				$output .= '<div class="poll-choice">
					<input type="radio" name="poll_choice" id="poll_choice" value="' . $choice['id'] . '" />'
						. $choice['choice'] .
				'</div>';
			}

			$output .= '</form>
					</div>' . "\n";

			$output .= '<div class="poll-timestamp">' .
					wfMessage( 'poll-createdago', Poll::getTimeAgo( $poll_info['timestamp'] ) )->text() .
				'</div>' . "\n";

			$output .= "\t\t\t\t\t" . '<div class="poll-button">
					<a class="poll-skip-link" href="javascript:void(0);">' .
						wfMessage( 'poll-skip' )->text() . '</a>
				</div>';

			if( $wgRequest->getInt( 'prev_id' ) ) {
				$p = new Poll();
				$poll_info_prev = $p->getPoll( $wgRequest->getInt( 'prev_id' ) );
				$poll_title = Title::makeTitle( NS_POLL, $poll_info_prev['question'] );
				$output .= '<div class="previous-poll">';

				$output .= '<div class="previous-poll-title">' . wfMessage( 'poll-previous-poll' )->text() .
					" - <a href=\"{$poll_title->getFullURL()}\">{$poll_info_prev['question']}</a></div>
					<div class=\"previous-sub-title\">"
						. wfMessage( 'poll-view-answered-times', $poll_info_prev['votes'] )->parse() .
					'</div>';

				$x = 1;

				foreach( $poll_info_prev['choices'] as $choice ) {
					if( $poll_info_prev['votes']  > 0 ) {
						$percent = round( $choice['votes'] / $poll_info_prev['votes'] * 100 );
						$bar_width = floor( 360 * ( $choice['votes'] / $poll_info_prev['votes'] ) );
					} else {
						$percent = 0;
						$bar_width = 0;
					}

					if ( empty( $choice['votes'] ) ) {
						$choice['votes'] = 0;
					}

					$bar_img = '<img src="' . $wgExtensionAssetsPath . '/PollNY/images/vote-bar-' . $x .
						'.gif" class="image-choice-' . $x .
						'" style="width:' . $bar_width . 'px;height:11px;"/>';
					$output .= "<div class=\"previous-poll-choice\">
								<div class=\"previous-poll-choice-left\">{$choice['choice']} ({$percent}%)</div>";

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
			if( $poll_info['status'] == 0 ) {
				$output .= '<div class="poll-closed">' .
					wfMessage( 'poll-closed' )->text() . '</div>';
			}

			// Display message if poll has been flagged
			if( $poll_info['status'] == 2 ) {
				$output .= '<div class="poll-closed">' .
					wfMessage( 'poll-flagged' )->text() . '</div>';
				if( !$wgUser->isAllowed( 'polladmin' ) ) {
					$show_results = false;
				}
			}

			if( $show_results ) {
				$x = 1;

				foreach( $poll_info['choices'] as $choice ) {
					if( $poll_info['votes'] > 0 ) {
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
					$bar_img = "<img src=\"{$wgExtensionAssetsPath}/PollNY/images/vote-bar-{$x}.gif\" class=\"image-choice-{$x}\" style=\"width:{$bar_width}px;height:12px;\"/>";

					$output .= "<div class=\"poll-choice\">
					<div class=\"poll-choice-left\">{$choice['choice']} ({$percent}%)</div>";

					$output .= "<div class=\"poll-choice-right\">{$bar_img} <span class=\"poll-choice-votes\">"
						. wfMessage( 'poll-votes', $choice['votes'] )->parse() .
					'</span></div>';
					$output .= '</div>';

					$x++;
				}
			}

			$output .= '<div class="poll-total-votes">(' .
				wfMessage( 'poll-based-on-votes', $poll_info['votes'] )->parse() .
			')</div>
			<div class="poll-timestamp">' .
				wfMessage( 'poll-createdago', Poll::getTimeAgo( $poll_info['timestamp'] ) )->parse() .
			'</div>


			<div class="poll-button">
				<input type="hidden" id="poll_id" name="poll_id" value="' . $poll_info['id'] . '" />
				<a class="poll-next-poll-link" href="javascript:void(0);">' .
					wfMessage( 'poll-next-poll' )->text() . '</a>
			</div>';
		}

		// "Embed this on a wiki page" feature
		$poll_embed_name = htmlspecialchars( $title->getText(), ENT_QUOTES );
		$output .= '<br />
			<table cellpadding="0" cellspacing="2" border="0">
				<tr>
					<td>
						<b>' . wfMessage( 'poll-embed' )->plain() . "</b>
					</td>
					<td>
						<form name=\"embed_poll\">
							<input name='embed_code' style='width:300px;font-size:10px;' type='text' value='<pollembed title=\"{$poll_embed_name}\" />' onclick='javascript:document.embed_poll.embed_code.focus();document.embed_poll.embed_code.select();' readonly='readonly' />
						</form>
					</td>
				</tr>
			</table>\n";

		$output .= '</div>' . "\n"; // .poll

		$output .= '<div class="cleared"></div>';

		$wgOut->addHTML( $output );

		global $wgPollDisplay;
		if( $wgPollDisplay['comments'] ) {
			$wgOut->addWikiText( '<comments/>' );
		}
	}
}