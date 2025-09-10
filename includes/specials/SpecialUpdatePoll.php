<?php

use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\UnlistedSpecialPage;
use MediaWiki\Title\Title;

class UpdatePoll extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'UpdatePoll' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Parameter passed to the page, if any
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

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->getBlock() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->setHeaders();

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the UpdatePoll page
		 */
		if ( $user->getId() == 0 ) {
			$out->setPageTitle( $this->msg( 'poll-woops' )->plain() );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			// If we want to edit a certain poll (as we probably do, given that
			// accessing this special page *without* the id URL query param results in
			// an "Invalid access" error), take care to preserve the "id" query param
			// so that the user is correctly redirected to Special:UpdatePoll?id=<poll ID>
			// upon login (T266612)
			$urlParams = [
				'returnto' => 'Special:UpdatePoll'
			];
			if ( $request->getInt( 'id' ) > 0 ) {
				$urlParams['returntoquery'] = wfArrayToCgi( [ 'id' => $request->getInt( 'id' ) ] );
			}
			$out->redirect( $login->getFullURL( $urlParams ) );
			return;
		}

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( [ 'ext.pollNY', 'ext.pollNY.file-selector' ] );

		if (
			$request->wasPosted() &&
			$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
			$_SESSION['alreadysubmitted'] == false
		) {
			$_SESSION['alreadysubmitted'] = true;

			$p = new Poll();
			$poll_info = $p->getPoll( $request->getInt( 'id' ) );

			$choices = '';
			$choicesWereChanged = false;
			$imageWasChanged = false;

			$services = MediaWikiServices::getInstance();
			$dbw = $services->getDBLoadBalancer()->getConnection( DB_PRIMARY );

			// Add Choices
			for ( $x = 1; $x <= 10; $x++ ) {
				if ( $request->getVal( "poll_answer_{$x}" ) ) {
					$dbw->update(
						'poll_choice',
						[ 'pc_text' => $request->getVal( "poll_answer_{$x}" ) ],
						[
							'pc_poll_id' => intval( $poll_info['id'] ),
							'pc_order' => $x
						],
						__METHOD__
					);

					$choices .= $request->getVal( "answer_{$x}" ) . "\n";

					$choicesWereChanged = true;
				}
			}

			// Update image
			if ( $request->getVal( 'poll_image_name' ) ) {
				$dbw->update(
					'poll_question',
					[ 'poll_image' => $request->getVal( 'poll_image_name' ) ],
					[ 'poll_id' => intval( $poll_info['id'] ) ],
					__METHOD__
				);

				$imageWasChanged = true;
			}

			$prev_qs = '';
			$poll_page = Title::newFromID( $request->getInt( 'id' ) );
			if ( $request->getInt( 'prev_poll_id' ) ) {
				$prev_qs = 'prev_id=' . $request->getInt( 'prev_poll_id' );
			}

			// Update poll wiki page
			$localizedCategoryNS = $services->getContentLanguage()->getNsText( NS_CATEGORY );
			$localizedFileNS = $services->getContentLanguage()->getNsText( NS_FILE );
			$page = $services->getWikiPageFactory()->newFromTitle( $poll_page );

			// Get the current page content so we can perform some (regex) replacement magic on it eventually...
			$lookupService = $services->getRevisionLookup();
			$currentRevision = $lookupService->getRevisionByTitle( $poll_page );
			if ( !$currentRevision ) {
				// ?!?
				return;
			}

			$currentContent = $currentRevision->getContent( MediaWiki\Revision\SlotRecord::MAIN );
			if ( !$currentContent ) {
				return;
			}

			'@phan-var TextContent $currentContent';
			$oldText = $currentContent->getText();

			if ( $choicesWereChanged ) {
				$oldText = preg_replace(
					'/<userpoll>(.*)<\/userpoll>/',
					"<userpoll>\n$choices</userpoll>",
					$oldText
				);
			}

			if ( $imageWasChanged ) {
				$newImageName = $request->getVal( 'poll_image_name' );
				// If there was *no* image at all...
				if ( !preg_match( '/\[\[' . $localizedFileNS . '\:/', $oldText ) ) {
					// ...just inject it into the wikitext, nice 'n' simple!
					$oldText = str_replace(
						"</userpoll>\n\n",
						'</userpoll>' . "\n\n[[" . $localizedFileNS . ':' . $newImageName . "|150px]]\n\n",
						$oldText
					);
				} else {
					// But if there already _was_ an image, we gotta resort to regex voodoo :-/
					$oldText = preg_replace(
						"/\<\/userpoll\>\n\n\[\[" . $localizedFileNS . '\:(.*)\|/',
						'</userpoll>' . "\n\n[[" . $localizedFileNS . ':' . $newImageName . '|',
						$oldText
					);
				}
			}

			$content = ContentHandler::makeContent( $oldText, $poll_page );
			$summary = $request->getText( 'wpSummary', '' );

			$page->doUserEditContent( $content, $user, $summary );

			// Redirect to new Poll Page
			$out->redirect( $poll_page->getFullURL( $prev_qs ) );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Display the form for updating a given poll (via the id parameter in the
	 * URL).
	 *
	 * @return string|false HTML string or bool false if the user can't edit the requested poll
	 */
	function displayForm() {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$p = new Poll();
		$poll_info = $p->getPoll( $request->getInt( 'id' ) );

		if ( !isset( $poll_info['id'] ) ||
			!( $user->isAllowed( 'polladmin' ) || $user->getActorId() == $poll_info['actor'] )
		) {
			$out->setPageTitle( $this->msg( 'poll-woops' )->plain() );
			$out->addHTML( $this->msg( 'poll-edit-invalid-access' )->escaped() );
			return false;
		}

		$poll_image_tag = '';
		if ( $poll_info['image'] ) {
			$poll_image_width = 150;
			$poll_image = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $poll_info['image'] );
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
		}

		$poll_page = Title::newFromID( $request->getInt( 'id' ) );
		$prev_qs = '';
		if ( $request->getInt( 'prev_poll_id' ) ) {
			$prev_qs = 'prev_id=' . $request->getInt( 'prev_poll_id' );
		}

		$out->setPageTitle( $this->msg( 'poll-edit-title', $poll_info['question'] )->plain() );

		$pollId = (int)$poll_info['id'];
		$form = "<div class=\"update-poll-left\">
			<form action=\"\" method=\"post\" enctype=\"multipart/form-data\" name=\"form1\">
			<input type=\"hidden\" name=\"poll_id\" value=\"{$pollId}\" />
			<input type=\"hidden\" name=\"prev_poll_id\" value=\"" . $request->getInt( 'prev_id' ) . '" />
			<input type="hidden" name="poll_image_name" id="poll_image_name" />

			<h1>' . $this->msg( 'poll-edit-answers' )->escaped() . '</h1>';

		$x = 1;
		foreach ( $poll_info['choices'] as $choice ) {
			$form .= "<div class=\"update-poll-answer\">
					<span class=\"update-poll-answer-number\">{$x}.</span>
					<input type=\"text\" tabindex=\"{$x}\" id=\"poll_answer_{$x}\" name=\"poll_answer_{$x}\" value=\"" .
						htmlspecialchars( $choice['choice'], ENT_QUOTES ) . '" />
				</div>';
			$x++;
		}

		$rightsText = $this->getConfig()->get( 'RightsText' );
		if ( $rightsText ) {
			$copywarnMsg = 'copyrightwarning';
			$copywarnMsgParams = [
				'[[' . $this->msg( 'copyrightpage' )->inContentLanguage()->plain() . ']]',
				$rightsText
			];
		} else {
			$copywarnMsg = 'copyrightwarning2';
			$copywarnMsgParams = [
				'[[' . $this->msg( 'copyrightpage' )->inContentLanguage()->plain() . ']]'
			];
		}

		$form .= '</div><!-- .update-poll-left -->

			<div class="update-poll-right">
			<h1>' . $this->msg( 'poll-edit-image' )->escaped() . "</h1>
			<div id=\"poll_image\" class=\"update-poll-image\">{$poll_image_tag}</div>

			<!--
				<div id=\"fake-form\" style=\"display:block;height:70px;\">
					<input type=\"file\" size=\"40\" disabled=\"disabled\" />
					<div style=\"margin:9px 0px 0px 0px;\">
						<input type=\"button\" value=\"Upload\"/>
					</div>
				</div>
			-->
			<div id=\"real-form\" style=\"display:block;height:90px;\">
				<iframe id=\"imageUpload-frame\" class=\"imageUpload-frame\" width=\"610\"
					scrolling=\"no\" frameborder=\"0\" src=\"" .
					htmlspecialchars( SpecialPage::getTitleFor( 'PollAjaxUpload' )->getFullURL( 'wpThumbWidth=75' ) ) . '">
				</iframe>
			</div>

		</div>
		<div class="visualClear"></div>
		<div class="update-poll-warning">' . $this->msg( $copywarnMsg, $copywarnMsgParams )->parse() . '</div>';

		// The edit summary field
		$form .= $this->msg( 'summary' )->escaped() . '<br />' . Html::input( 'wpSummary', '', 'text', [
			'title' => Linker::titleAttrib( 'summary' ),
			'accessKey' => Linker::accesskey( 'summary' ),
			'size' => 60
		] );

		$form .= '<div class="update-poll-buttons">' .
			Html::hidden( 'wpEditToken', $user->getEditToken() ) .
				"<input type=\"submit\" class=\"site-button\" value=\"" . $this->msg( 'poll-edit-button' )->escaped() . "\" size=\"20\" onclick=\"document.form1.submit()\" />
			<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'poll-cancel-button' )->escaped() . "\" size=\"20\" onclick=\"window.location='" . $poll_page->getFullURL( $prev_qs ) . "'\" />
		</div>
		</form>";

		return $form;
	}

}
