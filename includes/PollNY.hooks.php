<?php
/**
 * Class containing PollNY's hooked functions.
 * All functions are public and static.
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;

class PollNYHooks {

	/**
	 * Updates the poll_question table to point to the new title when a page in
	 * the NS_POLL namespace is moved.
	 *
	 * @param MediaWiki\Linker\LinkTarget $old Object referring to the old title
	 * @param MediaWiki\Linker\LinkTarget $new Object referring to the new (current) title
	 * @param MediaWiki\User\UserIdentity $userIdentity User performing the move [unused]
	 * @param int $oldid Old ID of the page
	 * @param int $newid New ID of the page [unused]
	 * @param string $reason User-supplied reason for moving the page
	 * @param MediaWiki\Revision\RevisionRecord $revision
	 */
	public static function updatePollQuestion(
		MediaWiki\Linker\LinkTarget $old,
		MediaWiki\Linker\LinkTarget $new,
		MediaWiki\User\UserIdentity $userIdentity,
		int $oldid,
		int $newid,
		string $reason,
		MediaWiki\Revision\RevisionRecord $revision
	) {
		if ( $old->getNamespace() == NS_POLL ) {
			$dbw = wfGetDB( DB_PRIMARY );
			$dbw->update(
				'poll_question',
				[ 'poll_text' => $new->getText() ],
				[ 'poll_page_id' => $oldid ],
				__METHOD__
			);
		}
	}

	/**
	 * Called when deleting a poll page to make sure that the appropriate poll
	 * database tables will be updated accordingly & memcached will be purged.
	 *
	 * @param WikiPage &$article instance of WikiPage class
	 * @param User &$user Unused
	 * @param string $reason deletion reason (unused)
	 */
	public static function deletePollQuestion( &$article, &$user, $reason ) {
		if ( $article->getTitle()->getNamespace() == NS_POLL ) {
			$dbw = wfGetDB( DB_PRIMARY );

			$s = $dbw->selectRow(
				'poll_question',
				[ 'poll_actor', 'poll_id' ],
				[ 'poll_page_id' => $article->getID() ],
				__METHOD__
			);
			if ( $s !== false ) {
				// Clear profile cache for user id that created poll
				$userId = User::newFromActorId( $s->poll_actor )->getId();
				$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
				$key = $cache->makeKey( 'user', 'profile', 'polls', $userId );
				$cache->delete( $key );

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
	}

	/**
	 * Rendering for the <userpoll> tag.
	 *
	 * @param Parser $parser
	 */
	public static function registerUserPollHook( Parser $parser ) {
		$parser->setHook( 'userpoll', [ 'PollNYHooks', 'renderPollNY' ] );
	}

	public static function renderPollNY( $input, $args, $parser ) {
		return '';
	}

	/**
	 * Handles the viewing of pages in the poll namespace.
	 *
	 * @param Title &$title
	 * @param Article &$article
	 */
	public static function pollFromTitle( &$title, &$article ) {
		if ( $title->getNamespace() == NS_POLL ) {
			global $wgRequest, $wgOut, $wgHooks;

			// We don't want caching here, it'll only cause problems...
			if ( method_exists( $wgOut, 'disableClientCache' ) ) {
				// MW 1.38+
				$wgOut->disableClientCache();
			} else {
				// Older MWs (1.35+)
				// @phan-suppress-next-line PhanParamTooMany
				$wgOut->enableClientCache( false );
			}
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
	}

	/**
	 * Mark page as uncacheable
	 *
	 * @param Parser $parser
	 * @param ParserOutput $output
	 */
	public static function onParserLimitReportPrepare( $parser, $output ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
	}

	/**
	 * Set up the <pollembed> tag for embedding polls on wiki pages.
	 *
	 * @param Parser $parser
	 */
	public static function registerPollEmbedHook( Parser $parser ) {
		$parser->setHook( 'pollembed', [ 'PollNYHooks', 'renderEmbedPoll' ] );
	}

	public static function followPollID( $pollTitle ) {
		if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
			// MW 1.36+
			$pollPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $pollTitle );
		} else {
			$pollPage = new WikiPage( $pollTitle );
		}

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
	 * @param string $input user input
	 * @param array $args arguments supplied to the pollembed tag
	 * @param Parser $parser
	 * @return string HTML or nothing
	 */
	public static function renderEmbedPoll( $input, $args, $parser ) {
		$poll_name = $args['title'];
		if ( $poll_name ) {
			global $wgOut, $wgExtensionAssetsPath, $wgPollDisplay;

			if ( method_exists( $parser, 'getUserIdentity' ) ) {
				// MW 1.36+
				$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $parser->getUserIdentity() );
			} else {
				// @phan-suppress-next-line PhanUndeclaredMethod
				$user = $parser->getUser();
			}
			// Load CSS
			$wgOut->addModuleStyles( 'ext.pollNY.css' );

			// Disable caching; this is important so that we don't cause subtle
			// bugs that are difficult to fix.
			if ( method_exists( $wgOut, 'disableClientCache' ) ) {
				// MW 1.38+
				$wgOut->disableClientCache();
			} else {
				// Older MWs (1.35+)
				// @phan-suppress-next-line PhanParamTooMany
				$wgOut->enableClientCache( false );
			}
			$parser->getOutput()->updateCacheExpiry( 0 );

			$poll_title = Title::newFromText( $poll_name, NS_POLL );
			$poll_title = self::followPollID( $poll_title );
			$poll_page_id = (int)$poll_title->getArticleID();

			if ( $poll_page_id > 0 ) {
				$p = new Poll();
				$poll_info = $p->getPoll( $poll_page_id );
				$poll_info['id'] = (int)$poll_info['id']; // paranoia

				$output = "\t\t" . '<div class="poll-embed-title">' .
					htmlspecialchars( $poll_info['question'], ENT_QUOTES ) .
				'</div>' . "\n";
				if ( $poll_info['image'] ) {
					$poll_image_width = 100;
					$poll_image = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $poll_info['image'] );
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
					$user->isAllowed( 'pollny-vote' ) &&
					!$p->userVoted( $user, $poll_info['id'] ) &&
					$poll_info['status'] == Poll::STATUS_OPEN
				) {
					$wgOut->addModules( 'ext.pollNY' );
					$output .= "<div id=\"loading-poll_{$poll_info['id']}\" class=\"poll-loading-msg\">" . wfMessage( 'poll-js-loading' )->escaped() . '</div>';
					$output .= "<div id=\"poll-display_{$poll_info['id']}\">";
					$output .= Html::openElement( 'form', [
						'name' => "poll_{$poll_info['id']}",
						'method' => 'post',
						'action' => $poll_title->getFullURL()
					] );
					$output .= "<input type=\"hidden\" id=\"poll_id_{$poll_info['id']}\" name=\"poll_id_{$poll_info['id']}\" value=\"{$poll_info['id']}\"/>";
					$output .= Html::hidden( 'poll_id', $poll_info['id'] );
					$output .= Html::hidden( 'wpEditToken', $user->getEditToken() );

					foreach ( $poll_info['choices'] as $choice ) {
						$choice['id'] = (int)$choice['id'];
						$output .= "<div class=\"poll-choice\">
						<input type=\"radio\" name=\"poll_choice\" data-poll-id=\"{$poll_info['id']}\" data-poll-page-id=\"{$poll_page_id}\" id=\"poll_choice\" value=\"{$choice['id']}\">";
						// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
						$output .= htmlspecialchars( $choice['choice'], ENT_QUOTES );
						$output .= '</div>';
					}

					$output .= Html::submitButton( wfMessage( 'poll-submit-btn' )->text(), [ 'class' => 'poll-vote-btn-nojs' ] );
					$output .= '</form>
						</div>';
				} else {
					// Display message if poll has been closed for voting
					if ( $poll_info['status'] == Poll::STATUS_CLOSED ) {
						$output .= '<div class="poll-closed">' .
							wfMessage( 'poll-closed' )->escaped() . '</div>';
					}

					$x = 1;

					foreach ( $poll_info['choices'] as $choice ) {
						// $percent = round( $choice['votes'] / $poll_info['votes'] * 100 );
						$percent = (int)$choice['percent'];
						if ( $poll_info['votes'] > 0 ) {
							$bar_width = floor( 480 * ( $choice['votes'] / $poll_info['votes'] ) );
						}
						$bar_img = Html::element( 'img', [
							'src' => $wgExtensionAssetsPath . "/SocialProfile/images/vote-bar-{$x}.gif",
							'border' => '0',
							'class' => "image-choice-{$x}",
							'style' => "width:{$percent}%;height:12px;",
							'alt' => ''
						] );

						$safeChoice = htmlspecialchars( $choice['choice'], ENT_QUOTES );
						$output .= "<div class=\"poll-choice\">
						<div class=\"poll-choice-left\">{$safeChoice} ({$percent}%)</div>";

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
							wfMessage( 'poll-discuss' )->escaped() . '</a></div>';
					}
					$output .= '<div class="poll-timestamp">' .
						wfMessage( 'poll-createdago', Poll::getTimeAgo( $poll_info['timestamp'] ) )->parse() .
					'</div>';
				}

				return $output;
			} else {
				// Poll doesn't exist or is unavailable for some other reason
				$output = '<div class="poll-embed-title">' .
					wfMessage( 'poll-unavailable' )->escaped() . '</div>';
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

		$db = $updater->getDB();
		if ( $db->getType() === 'postgres' ) {
			$sqlDirectory .= 'postgres/';
		}

		$updater->addExtensionTable( 'poll_choice', $sqlDirectory . 'poll_choice.sql' );
		$updater->addExtensionTable( 'poll_question', $sqlDirectory . 'poll_question.sql' );
		$updater->addExtensionTable( 'poll_user_vote', $sqlDirectory . 'poll_user_vote.sql' );

		if ( !in_array( $db->getType(), [ 'postgres', 'sqlite' ] ) ) {
			$updater->modifyExtensionField( 'poll_choice', 'pc_vote_count',
				$sqlDirectory . 'patches/poll_choice_alter_pc_vote_count.sql' );
		}

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
	 * Dumb hack to make ApiPollNY conditionally recognize 'token' as a valid parameter.
	 * Normally this is done in ApiBase#getFinalParams but normal API modules also
	 * implement token as a true bool (either a module requires a token or doesn't),
	 * whereas ours is more of a...tri-state boolean, if you will. (ApiPollNY supports
	 * 5 different actions of which 3 require a token and 2 don't.)
	 *
	 * @param ApiBase &$apiModule ApiBase subclass (we only care about ApiPollNY)
	 * @param array &$params URL parameters recognized by the API module
	 * @param int $flags
	 */
	public static function onAPIGetAllowedParams( &$apiModule, &$params, $flags ) {
		if ( get_class( $apiModule ) === 'ApiPollNY' ) {
			if ( $apiModule->shouldRequireToken ) {
				$params['token'] = [
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
					ApiBase::PARAM_SENSITIVE => true,
					ApiBase::PARAM_HELP_MSG => [
						'api-help-param-token',
						'csrf',
					],
				] + ( $params['token'] ?? [] );
			}
		}
	}
}
