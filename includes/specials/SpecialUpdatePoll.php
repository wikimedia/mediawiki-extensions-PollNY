<?php

class UpdatePoll extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'UpdatePoll' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
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
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->setHeaders();

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the UpdatePoll page
		 */
		if ( $user->getID() == 0 ) {
			$out->setPageTitle( $this->msg( 'poll-woops' )->plain() );
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( $login->getFullURL( 'returnto=Special:UpdatePoll' ) );
			return false;
		}

		// Add CSS & JS
		$out->addModuleStyles( 'ext.pollNY.css' );
		$out->addModules( 'ext.pollNY' );

		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;
			$p = new Poll();
			$poll_info = $p->getPoll( $request->getInt( 'id' ) );

			// Add Choices
			for ( $x = 1; $x <= 10; $x++ ) {
				if ( $request->getVal( "poll_answer_{$x}" ) ) {
					$dbw = wfGetDB( DB_MASTER );

					$dbw->update(
						'poll_choice',
						[ 'pc_text' => $request->getVal( "poll_answer_{$x}" ) ],
						[
							'pc_poll_id' => intval( $poll_info['id'] ),
							'pc_order' => $x
						],
						__METHOD__
					);
				}
			}

			// Update image
			if ( $request->getVal( 'poll_image_name' ) ) {
				$dbw = wfGetDB( DB_MASTER );

				$dbw->update(
					'poll_question',
					[ 'poll_image' => $request->getVal( 'poll_image_name' ) ],
					[ 'poll_id' => intval( $poll_info['id'] ) ],
					__METHOD__
				);
			}

			$prev_qs = '';
			$poll_page = Title::newFromID( $request->getInt( 'id' ) );
			if ( $request->getInt( 'prev_poll_id' ) ) {
				$prev_qs = 'prev_id=' . $request->getInt( 'prev_poll_id' );
			}

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
	 * @return string|false HTML
	 */
	function displayForm() {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$p = new Poll();
		$poll_info = $p->getPoll( $request->getInt( 'id' ) );

		if ( !isset( $poll_info['id'] ) ||
			!( $user->isAllowed( 'polladmin' ) || $user->getID() == $poll_info['user_id'] )
		) {
			$out->setPageTitle( $this->msg( 'poll-woops' )->plain() );
			$out->addHTML( $this->msg( 'poll-edit-invalid-access' )->text() );
			return false;
		}

		$poll_image_tag = '';
		if ( $poll_info['image'] ) {
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
		}

		$poll_page = Title::newFromID( $request->getInt( 'id' ) );
		$prev_qs = '';
		if ( $request->getInt( 'prev_poll_id' ) ) {
			$prev_qs = 'prev_id=' . $request->getInt( 'prev_poll_id' );
		}

		$out->setPageTitle( $this->msg( 'poll-edit-title', $poll_info['question'] )->plain() );

		$form = "<div class=\"update-poll-left\">
			<form action=\"\" method=\"post\" enctype=\"multipart/form-data\" name=\"form1\">
			<input type=\"hidden\" name=\"poll_id\" value=\"{$poll_info['id']}\" />
			<input type=\"hidden\" name=\"prev_poll_id\" value=\"" . $request->getInt( 'prev_id' ) . '" />
			<input type="hidden" name="poll_image_name" id="poll_image_name" />

			<h1>' . $this->msg( 'poll-edit-answers' )->text() . '</h1>';

		$x = 1;
		foreach ( $poll_info['choices'] as $choice ) {
			$form .= "<div class=\"update-poll-answer\">
					<span class=\"update-poll-answer-number\">{$x}.</span>
					<input type=\"text\" tabindex=\"{$x}\" id=\"poll_answer_{$x}\" name=\"poll_answer_{$x}\" value=\"" .
						htmlspecialchars( $choice['choice'], ENT_QUOTES ) . '" />
				</div>';
			$x++;
		}

		global $wgRightsText;
		if ( $wgRightsText ) {
			$copywarnMsg = 'copyrightwarning';
			$copywarnMsgParams = [
				'[[' . $this->msg( 'copyrightpage' )->inContentLanguage()->plain() . ']]',
				$wgRightsText
			];
		} else {
			$copywarnMsg = 'copyrightwarning2';
			$copywarnMsgParams = [
				'[[' . $this->msg( 'copyrightpage' )->inContentLanguage()->plain() . ']]'
			];
		}

		$form .= '</form>
			</div><!-- .update-poll-left -->

			<div class="update-poll-right">
			<h1>' . $this->msg( 'poll-edit-image' )->plain() . "</h1>
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
		<div class="update-poll-warning">' . $this->msg( $copywarnMsg, $copywarnMsgParams )->parse() . "</div>
		<div class=\"update-poll-buttons\">
			<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'poll-edit-button' )->plain() . "\" size=\"20\" onclick=\"document.form1.submit()\" />
			<input type=\"button\" class=\"site-button\" value=\"" . $this->msg( 'poll-cancel-button' )->plain() . "\" size=\"20\" onclick=\"window.location='" . $poll_page->getFullURL( $prev_qs ) . "'\" />
		</div>";
		return $form;
	}
}
