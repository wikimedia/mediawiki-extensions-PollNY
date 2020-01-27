<?php
/**
 * Class containing PollNY's hooked functions.
 * All functions are public and static.
 *
 * @file
 * @ingroup Extensions
 */
class PollNYHooks {

	/**
	 * Updates the poll_question table to point to the new title when a page in
	 * the NS_POLL namespace is moved.
	 *
	 * @param $title Object: Title object referring to the old title
	 * @param $newTitle Object: Title object referring to the new (current)
	 *                          title
	 * @param $user Object: User object performing the move [unused]
	 * @param $oldid Integer: old ID of the page
	 * @param $newid Integer: new ID of the page [unused]
	 * @return Boolean true
	 */
	public static function updatePollQuestion( &$title, &$newTitle, $user, $oldid, $newid ) {
		if ( $title->getNamespace() == NS_POLL ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->update(
				'poll_question',
				[ 'poll_text' => $newTitle->getText() ],
				[ 'poll_page_id' => intval( $oldid ) ],
				__METHOD__
			);
		}
		return true;
	}

	/**
	 * Called when deleting a poll page to make sure that the appropriate poll
	 * database tables will be updated accordingly & memcached will be purged.
	 *
	 * @param $article Object: instance of Article class
	 * @param $user Unused
	 * @param $reason Mixed: deletion reason (unused)
	 * @return Boolean true
	 */
	public static function deletePollQuestion( &$article, &$user, $reason ) {
		if ( $article->getTitle()->getNamespace() == NS_POLL ) {
			$dbw = wfGetDB( DB_MASTER );

			$s = $dbw->selectRow(
				'poll_question',
				[ 'poll_actor', 'poll_id' ],
				[ 'poll_page_id' => $article->getID() ],
				__METHOD__
			);
			if ( $s !== false ) {
				// Clear profile cache for user id that created poll
				global $wgMemc;
				$userId = User::newFromActorId( $s->poll_actor )->getId();
				$key = $wgMemc->makeKey( 'user', 'profile', 'polls', $userId );
				$wgMemc->delete( $key );

				// Delete poll record
				$dbw->delete(
					'poll_user_vote',
					[ 'pv_poll_id' => $s->poll_id ],
					__METHOD__
				);
				$dbw->delete(
					'poll_choice',
					[ 'pc_poll_id' => $s->poll_id ],
					__METHOD__
				);
				$dbw->delete(
					'poll_question',
					[ 'poll_page_id' => $article->getID() ],
					__METHOD__
				);
			}
		}

		return true;
	}

	/**
	 * Rendering for the <userpoll> tag.
	 *
	 * @param $parser Object: instace of Parser class
	 * @return Boolean true
	 */
	public static function registerUserPollHook( &$parser ) {
		$parser->setHook( 'userpoll', [ 'PollNYHooks', 'renderPollNY' ] );
		return true;
	}

	public static function renderPollNY( $input, $args, $parser ) {
		return '';
	}

	/**
	 * Handles the viewing of pages in the poll namespace.
	 *
	 * @param $title Object: instance of Title class
	 * @param $article Object: instance of Article class
	 * @return Boolean true
	 */
	public static function pollFromTitle( &$title, &$article ) {
		if ( $title->getNamespace() == NS_POLL ) {
			global $wgRequest, $wgOut;

			// We don't want caching here, it'll only cause problems...
			$wgOut->enableClientCache( false );
			$wgHooks['ParserLimitReportPrepare'][] = 'PollNYHooks::onParserLimitReportPrepare';

			// Prevents editing of polls
			if ( $wgRequest->getVal( 'action' ) == 'edit' ) {
				if ( $title->getArticleID() == 0 ) {
					$create = SpecialPage::getTitleFor( 'CreatePoll' );
					$wgOut->redirect(
						$create->getFullURL( 'wpDestName=' . $title->getText() )
					);
				} else {
					$update = SpecialPage::getTitleFor( 'UpdatePoll' );
					$wgOut->redirect(
						$update->getFullURL( 'id=' . $title->getArticleID() )
					);
				}
			}

			// Add required JS & CSS
			$wgOut->addModules( 'ext.pollNY' );
			$wgOut->addModuleStyles( 'ext.pollNY.css' );

			$article = new PollPage( $title );
		}

		return true;
	}

	/**
	 * Mark page as uncacheable
	 *
	 * @param Parser $parser
	 * @param ParserOutput $limitReport
	 * @return bool true
	 */
	public static function onParserLimitReportPrepare( $parser, $output ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		return true;
	}

	/**
	 * Set up the <pollembed> tag for embedding polls on wiki pages.
	 *
	 * @param $parser Object: instance of Parser class
	 * @return Boolean true
	 */
	public static function registerPollEmbedHook( &$parser ) {
		$parser->setHook( 'pollembed', [ 'PollNYHooks', 'renderEmbedPoll' ] );
		return true;
	}

	public static function followPollID( $pollTitle ) {
		$pollPage = new WikiPage( $pollTitle );

		if ( $pollPage->isRedirect() ) {
			$pollTitle = $pollPage->followRedirect();
			return self::followPollID( $pollTitle );
		} else {
			return $pollTitle;
		}
	}

	/**
	 * Callback function for the <pollembed> tag.
	 *
	 * @param $input Mixed: user input
	 * @param $args Array: arguments supplied to the pollembed tag
	 * @param $parser Object: instance of Parser class
	 * @return HTML or nothing
	 */
	public static function renderEmbedPoll( $input, $args, $parser ) {
		$poll_name = $args['title'];
		if ( $poll_name ) {
			global $wgOut, $wgUser, $wgExtensionAssetsPath, $wgPollDisplay;

			// Load CSS
			$wgOut->addModuleStyles( 'ext.pollNY.css' );

			// Disable caching; this is important so that we don't cause subtle
			// bugs that are a bitch to fix.
			$wgOut->enableClientCache( false );
			$parser->getOutput()->updateCacheExpiry( 0 );

			$poll_title = Title::newFromText( $poll_name, NS_POLL );
			$poll_title = self::followPollID( $poll_title );
			$poll_page_id = $poll_title->getArticleID();

			if ( $poll_page_id > 0 ) {
				$p = new Poll();
				$poll_info = $p->getPoll( $poll_page_id );

				$output = "\t\t" . '<div class="poll-embed-title">' .
					$poll_info['question'] .
				'</div>' . "\n";
				if ( $poll_info['image'] ) {
					$poll_image_width = 100;
					$poll_image = wfFindFile( $poll_info['image'] );
					$width = $poll_image_url = '';
					if ( is_object( $poll_image ) ) {
						$poll_image_url = $poll_image->createThumb( $poll_image_width );
						if ( $poll_image->getWidth() >= $poll_image_width ) {
							$width = $poll_image_width;
						} else {
							$width = $poll_image->getWidth();
						}
					}
					$poll_image_tag = '<img width="' . $width . '" alt="" src="' . $poll_image_url . '" />';
					$output .= "\t\t<div class=\"poll-image\">{$poll_image_tag}</div>\n";
				}

				// If the user hasn't voted for this poll yet, they're allowed
				// to do so and the poll is open for votes, display the question
				// and let the user vote
				if (
					$wgUser->isAllowed( 'pollny-vote' ) &&
					!$p->userVoted( $wgUser, $poll_info['id'] ) &&
					$poll_info['status'] == 1
				) {
					$wgOut->addModules( 'ext.pollNY' );
					$output .= "<div id=\"loading-poll_{$poll_info['id']}\" class=\"poll-loading-msg\">" . wfMessage( 'poll-js-loading' )->text() . '</div>';
					$output .= "<div id=\"poll-display_{$poll_info['id']}\" style=\"display:none;\">";
					$output .= "<form name=\"poll_{$poll_info['id']}\"><input type=\"hidden\" id=\"poll_id_{$poll_info['id']}\" name=\"poll_id_{$poll_info['id']}\" value=\"{$poll_info['id']}\"/>";

					foreach ( $poll_info['choices'] as $choice ) {
						$output .= "<div class=\"poll-choice\">
						<input type=\"radio\" name=\"poll_choice\" data-poll-id=\"{$poll_info['id']}\" data-poll-page-id=\"{$poll_page_id}\" id=\"poll_choice\" value=\"{$choice['id']}\">{$choice['choice']}
						</div>";
					}

					$output .= '</form>
						</div>';
				} else {
					// Display message if poll has been closed for voting
					if ( $poll_info['status'] == 0 ) {
						$output .= '<div class="poll-closed">' .
							wfMessage( 'poll-closed' )->text() . '</div>';
					}

					$x = 1;

					foreach ( $poll_info['choices'] as $choice ) {
						// $percent = round( $choice['votes'] / $poll_info['votes'] * 100 );
						if ( $poll_info['votes'] > 0 ) {
							$bar_width = floor( 480 * ( $choice['votes'] / $poll_info['votes'] ) );
						}
						$bar_img = "<img src=\"{$wgExtensionAssetsPath}/SocialProfile/images/vote-bar-{$x}.gif\" border=\"0\" class=\"image-choice-{$x}\" style=\"width:{$choice['percent']}%;height:12px;\" alt=\"\" />";

						$output .= "<div class=\"poll-choice\">
						<div class=\"poll-choice-left\">{$choice['choice']} ({$choice['percent']}%)</div>";

						// If the amount of votes is not set, set it to 0
						// This fixes an odd bug where "votes" would be shown
						// instead of "0 votes" when using the pollembed tag.
						if ( empty( $choice['votes'] ) ) {
							$choice['votes'] = 0;
						}

						$output .= "<div class=\"poll-choice-right\">{$bar_img} <span class=\"poll-choice-votes\">" .
							wfMessage( 'poll-votes', $choice['votes'] )->parse() . '</span></div>';
						$output .= '</div>';

						$x++;
					}

					$output .= '<div class="poll-total-votes">(' .
						wfMessage(
							'poll-based-on-votes',
							$poll_info['votes']
						)->parse() . ')</div>';
					if ( isset( $wgPollDisplay['comments'] ) && $wgPollDisplay['comments'] ) {
						$output .= '<div><a href="' . htmlspecialchars( $poll_title->getFullURL() ) . '">' .
							wfMessage( 'poll-discuss' )->text() . '</a></div>';
					}
					$output .= '<div class="poll-timestamp">' .
						wfMessage( 'poll-createdago', Poll::getTimeAgo( $poll_info['timestamp'] ) )->parse() .
					'</div>';
				}

				return $output;
			} else {
				// Poll doesn't exist or is unavailable for some other reason
				$output = '<div class="poll-embed-title">' .
					wfMessage( 'poll-unavailable' )->text() . '</div>';
				return $output;
			}
		}

		return '';
	}

	/**
	 * Adds the three new tables to the database when the user runs
	 * maintenance/update.php and perform other necessary upgrades for users
	 * upgrading from an older version of the extension.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$sqlDirectory = __DIR__ . '/../sql/';

		$updater->addExtensionTable( 'poll_choice', $sqlDirectory . 'poll_choice.sql' );
		$updater->addExtensionTable( 'poll_question', $sqlDirectory . 'poll_question.sql' );
		$updater->addExtensionTable( 'poll_user_vote', $sqlDirectory . 'poll_user_vote.sql' );

		$updater->modifyExtensionField( 'poll_choice', 'pc_vote_count',
			$sqlDirectory . 'patches/poll_choice_alter_pc_vote_count.sql' );

		$db = $updater->getDB();

		// Actor support
		$pollQuestionTableHasActorField = $db->fieldExists( 'poll_question', 'poll_actor', __METHOD__ );
		$pollUserVoteTableHasActorField = $db->fieldExists( 'poll_user_vote', 'pv_actor', __METHOD__ );

		if ( !$pollQuestionTableHasActorField ) {
			// 1) add new actor column
			$updater->addExtensionField( 'poll_question', 'poll_actor', $sqlDirectory . 'patches/actor/add_poll_actor_field_to_poll_question.sql' );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'poll_question', 'poll_actor', $sqlDirectory . 'patches/actor/add_poll_actor_index_to_poll_question.sql' );
		}

		if ( !$pollUserVoteTableHasActorField ) {
			// 1) add new actor column
			$updater->addExtensionField( 'poll_user_vote', 'pv_actor', $sqlDirectory . 'patches/actor/add_pv_actor_field_to_poll_user_vote.sql' );
			// 2) add the corresponding index
			$updater->addExtensionIndex( 'poll_user_vote', 'pv_actor', $sqlDirectory . 'patches/actor/add_pv_actor_index_to_poll_user_vote.sql' );
		}

		if (
			$db->fieldExists( 'poll_question', 'poll_actor', __METHOD__ ) &&
			$db->fieldExists( 'poll_question', 'poll_user_name', __METHOD__ )
		) {
			// 3) populate the columns with correct values
			// PITFALL WARNING! Do NOT change this to $updater->runMaintenance,
			// THEY ARE NOT THE SAME THING and this MUST be using addExtensionUpdate
			// instead for the code to work as desired!
			// HT Skizzerz
			$updater->addExtensionUpdate( [
				'runMaintenance',
				'MigrateOldPollNYUserColumnsToActor',
				'../maintenance/migrateOldPollNYUserColumnsToActor.php'
			] );

			// 4) drop old columns + indexes
			$updater->dropExtensionField( 'poll_question', 'poll_user_name', $sqlDirectory . 'patches/actor/drop_poll_user_name_field_from_poll_question.sql' );
			$updater->dropExtensionField( 'poll_question', 'poll_user_id', $sqlDirectory . 'patches/actor/drop_poll_user_id_field_from_poll_question.sql' );
			$updater->dropExtensionIndex( 'poll_question', 'poll_user_id', $sqlDirectory . 'patches/actor/drop_poll_user_id_index_from_poll_question.sql' );

			$updater->dropExtensionField( 'poll_user_vote', 'pv_user_name', $sqlDirectory . 'patches/actor/drop_pv_user_name_field_from_poll_user_vote.sql' );
			$updater->dropExtensionField( 'poll_user_vote', 'pv_user_id', $sqlDirectory . 'patches/actor/drop_pv_user_id_field_from_poll_user_vote.sql' );
			$updater->dropExtensionIndex( 'poll_user_vote', 'pv_user_id', $sqlDirectory . 'patches/actor/drop_pv_user_id_index_from_poll_user_vote.sql' );
		}
	}

	/**
	 * Register the canonical names for our namespace and its talkspace.
	 *
	 * @param $list Array: array of namespace numbers with corresponding
	 *                     canonical names
	 * @return Boolean true
	 */
	public static function onCanonicalNamespaces( &$list ) {
		$list[NS_POLL] = 'Poll';
		$list[NS_POLL_TALK] = 'Poll_talk';
		return true;
	}
}
