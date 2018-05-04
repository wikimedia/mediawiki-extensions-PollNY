<?php
/**
 * A special page to redirect the user to a randomly-chosen poll.
 * @file
 * @ingroup Extensions
 */
class RandomPoll extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'RandomPoll' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();

		$p = new Poll();

		$pollPage = $p->getRandomPollURL( $this->getUser()->getName() );
		if ( $pollPage == 'error' ) {
			$out->setPageTitle( $this->msg( 'poll-no-more-title' )->plain() );
			$out->addWikiMsg( 'poll-no-more-message' );
		} else {
			$pollTitle = Title::newFromText( $pollPage );
			$out->redirect( $pollTitle->getFullURL() );
		}

		return $pollPage;
	}

	protected function getGroupName() {
		return 'poll';
	}
}