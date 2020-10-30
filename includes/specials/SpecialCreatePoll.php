<?php

use MediaWiki\MediaWikiServices;

/**
 * A special page for creating new polls.
 * @file
 * @ingroup Extensions
 */
class CreatePoll extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CreatePoll' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param mixed $par Parameter passed to the page or null
	 * @return bool|string
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

		// Blocked users cannot create polls
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Check that the DB isn't locked
		$this->checkReadOnly();

		$this->setHeaders();

		/**
		 * Redirect anonymous users to login page
		 * It will automatically return them to the CreatePoll page
		 */
		if ( $user->getId() === 0 ) {
			$out->setPageTitle( $this->msg( 'poll-woops' )->plain() );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( $login->getLocalURL( 'returnto=Special:CreatePoll' ) );
			return false;
		}

		/**
		 * Create Poll Thresholds based on User Stats
		 */
		global $wgCreatePollThresholds;
		if ( is_array( $wgCreatePollThresholds ) && count( $wgCreatePollThresholds ) > 0 ) {
			$canCreate = true;

			$stats = new UserStats( $user->getId(), $user->getName() );
			$stats_data = $stats->getUserStats();

			$threshold_reason = '';
			foreach ( $wgCreatePollThresholds as $field => $threshold ) {
				if ( $stats_data[$field] < $threshold ) {
					$canCreate = false;
					$threshold_reason .= ( $threshold_reason ? ', ' : '' ) . "$threshold $field";
				}
			}

			if ( $canCreate === false ) {
				$out->setPageTitle( $this->msg( 'poll-create-threshold-title' )->plain() );
				$out->addWikiMsg( 'poll-create-threshold-reason', $threshold_reason );
				return '';
			}
		}

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( 'ext.pollNY' );

		// If the request was POSTed, try creating the poll
		if (
			$request->wasPosted() &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
			$_SESSION['alreadysubmitted'] == false
		) {
			$_SESSION['alreadysubmitted'] = true;

			// Add poll
			$poll_title = Title::makeTitleSafe( NS_POLL, $request->getVal( 'poll_question' ) );
			if ( $poll_title === null && !$poll_title instanceof Title ) {
				$out->setPageTitle( $this->msg( 'poll-create-threshold-title' )->plain() );
				$out->addWikiMsg( 'poll-create-threshold-reason', $threshold_reason );
				return '';
			}

			// Put choices in wikitext (so we can track changes)
			$choices = '';
			for ( $x = 1; $x <= 10; $x++ ) {
				if ( $request->getVal( "answer_{$x}" ) ) {
					$choices .= $request->getVal( "answer_{$x}" ) . "\n";
				}
			}

			$services = MediaWikiServices::getInstance();

			// Create poll wiki page
			$localizedCategoryNS = $services->getContentLanguage()->getNsText( NS_CATEGORY );
			$page = WikiPage::factory( $poll_title );
			$content = ContentHandler::makeContent(
				"<userpoll>\n$choices</userpoll>\n\n[[" .
					$localizedCategoryNS . ':' .
					$this->msg( 'poll-category' )->inContentLanguage()->plain() . "]]\n" .
				'[[' . $localizedCategoryNS . ':' .
					$this->msg( 'poll-category-user', $user->getName() )->inContentLanguage()->text() . "]]\n" .
				'[[' . $localizedCategoryNS . ":{{subst:CURRENTMONTHNAME}} {{subst:CURRENTDAY}}, {{subst:CURRENTYEAR}}]]\n\n__NOEDITSECTION__",
				$poll_title
			);
			$page->doEditContent(
				$content,
				$this->msg( 'poll-edit-desc' )->inContentLanguage()->plain()
			);

			$newPageId = $page->getId();

			$p = new Poll();
			$poll_id = $p->addPollQuestion(
				$request->getVal( 'poll_question' ),
				$request->getVal( 'poll_image_name' ),
				$newPageId,
				$user
			);

			// Add choices
			for ( $x = 1; $x <= 10; $x++ ) {
				if ( $request->getVal( "answer_{$x}" ) ) {
					$p->addPollChoice(
						$poll_id,
						$request->getVal( "answer_{$x}" ),
						$x
					);
				}
			}

			// Clear poll cache
			$cache = $services->getMainWANObjectCache();
			$key = $cache->makeKey( 'user', 'profile', 'polls', $user->getId() );
			$cache->delete( $key );

			// Redirect to new poll page
			$out->redirect( $poll_title->getFullURL() );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$template = new CreatePollTemplate;
			// Expose _this_ class to the GUI template
			$template->set( 'parentClass', $this );
			// And output the template!
			$out->addTemplate( $template );
		}
	}

	protected function getGroupName() {
		return 'poll';
	}
}
