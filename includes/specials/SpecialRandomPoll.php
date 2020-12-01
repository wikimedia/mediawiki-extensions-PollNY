<?php
/**
 * A special page to redirect the user to a randomly-chosen poll.
 *
 * @file
 * @ingroup Extensions
 */
class RandomPoll extends SpecialPage {

	public function __construct() {
		parent::__construct( 'RandomPoll' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Parameter passed to the page, if any [unused]
	 */
	public function execute( $par ) {
		$out = $this->getOutput();

		$this->setHeaders();

		$p = new Poll();

		$pollPage = $p->getRandomPollURL( $this->getUser() );
		if ( $pollPage == 'error' ) {
			$out->setPageTitle( $this->msg( 'poll-no-more-title' )->plain() );
			$out->addWikiMsg( 'poll-no-more-message' );
		} else {
			$pollTitle = Title::newFromText( $pollPage );
			$out->redirect( $pollTitle->getFullURL() );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'poll';
	}
}
