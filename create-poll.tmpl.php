<?php
/**
 * HTML template for Special:CreatePoll.
 * This used to be in its own function, as SpecialCreatePoll::displayForm(),
 * but since this doesn't use that many PHP bits, it's clearer when done by
 * using a template.
 *
 * @file
 * @ingroup Templates
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

class CreatePollTemplate extends QuickTemplate {

	function execute() {
		$parentClass = $this->data['parentClass'];

		$parentClass->getOutput()->setPageTitle( $parentClass->msg( 'poll-create-title' )->plain() );
		$iframeTitle = SpecialPage::getTitleFor( 'PollAjaxUpload' );
?>
	<div class="create-poll-top">
		<h1><?php echo $parentClass->msg( 'poll-create-title' )->text() ?></h1>
		<?php echo $parentClass->msg( 'poll-instructions' )->text() ?>
		<p><input type="button" class="site-button" value="<?php echo $parentClass->msg( 'poll-take-button' )->text() ?>" /></p>
	</div>

	<div class="create-poll-question-and-answer">
		<form action="" method="post" enctype="multipart/form-data" name="form1">
			<input type="hidden" name="poll_image_name" id="poll_image_name" />

			<h1><?php echo $parentClass->msg( 'poll-question-label' )->text() ?></h1>
			<div class="create-poll-question">
				<input type="text" id="poll_question" name="poll_question" class="createbox" style="width: 450px" value="<?php echo htmlspecialchars( $parentClass->getRequest()->getVal( 'wpDestName' ), ENT_QUOTES ) ?>" />
			</div>

			<div class="create-poll-answers">
				<h1><?php echo $parentClass->msg( 'poll-choices-label' )->text() ?></h1>
				<div class="create-poll-answer" id="poll_answer_1"><span class="create-poll-answer-number">1.</span><input type="text" id="answer_1" name="answer_1" /></div>
				<div class="create-poll-answer" id="poll_answer_2"><span class="create-poll-answer-number">2.</span><input type="text" id="answer_2" name="answer_2" /></div>
				<div class="create-poll-answer" id="poll_answer_3" style="display: none;"><span class="create-poll-answer-number">3.</span><input type="text" id="answer_3" name="answer_3" /></div>
				<div class="create-poll-answer" id="poll_answer_4" style="display: none;"><span class="create-poll-answer-number">4.</span><input type="text" id="answer_4" name="answer_4" /></div>
				<div class="create-poll-answer" id="poll_answer_5" style="display: none;"><span class="create-poll-answer-number">5.</span><input type="text" id="answer_5" name="answer_5" /></div>
				<div class="create-poll-answer" id="poll_answer_6" style="display: none;"><span class="create-poll-answer-number">6.</span><input type="text" id="answer_6" name="answer_6" /></div>
				<div class="create-poll-answer" id="poll_answer_7" style="display: none;"><span class="create-poll-answer-number">7.</span><input type="text" id="answer_7" name="answer_7" /></div>
				<div class="create-poll-answer" id="poll_answer_8" style="display: none;"><span class="create-poll-answer-number">8.</span><input type="text" id="answer_8" name="answer_8" /></div>
				<div class="create-poll-answer" id="poll_answer_9" style="display: none;"><span class="create-poll-answer-number">9.</span><input type="text" id="answer_9" name="answer_9" /></div>
				<div class="create-poll-answer" id="poll_answer_10" style="display: none;"><span class="create-poll-answer-number">10.</span><input type="text" id="answer_10" name="answer_10" /></div>
			</div>
		</form>
	</div>

	<div class="create-poll-image">
		<h1><?php echo $parentClass->msg( 'poll-image-label' )->text() ?></h1>
		<div id="poll_image"></div>

		<div id="real-form">
			<iframe id="imageUpload-frame" class="imageUpload-frame" src="<?php echo htmlspecialchars( $iframeTitle->getFullURL( 'wpThumbWidth=75' ) ) ?>"></iframe>
		</div>
	</div>

	<div class="create-poll-buttons">
		<input type="button" class="site-button" id="poll-create-button" value="<?php echo $parentClass->msg( 'poll-create-button' )->plain() ?>" size="20" />
		<input type="button" class="site-button" value="<?php echo $parentClass->msg( 'poll-cancel-button' )->plain() ?>" size="20" onclick="history.go(-1)" />
	</div>
<?php
	}
}
