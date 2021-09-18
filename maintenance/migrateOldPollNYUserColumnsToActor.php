<?php
/**
 * @file
 * @ingroup Maintenance
 */
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Run automatically with update.php
 *
 * @since January 2020
 */
class MigrateOldPollNYUserColumnsToActor extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrates data from old _user_name/_user_id columns in poll_question and poll_user_vote tables to the new actor columns.' );
	}

	/**
	 * Get the update key name to go in the update log table
	 *
	 * @return string
	 */
	protected function getUpdateKey() {
		return __CLASS__;
	}

	/**
	 * Message to show that the update was done already and was just skipped
	 *
	 * @return string
	 */
	protected function updateSkippedMessage() {
		return 'poll_question and poll_user_vote have already been migrated to use the actor columns.';
	}

	/**
	 * Do the actual work.
	 *
	 * @return bool True to log the update as done
	 */
	protected function doDBUpdates() {
		$dbw = $this->getDB( DB_PRIMARY );
		$dbw->query(
			"UPDATE {$dbw->tableName( 'poll_question' )} SET poll_actor=(SELECT actor_id FROM {$dbw->tableName( 'actor' )} WHERE actor_user=poll_user_id AND actor_name=poll_user_name)",
			__METHOD__
		);
		$dbw->query(
			"UPDATE {$dbw->tableName( 'poll_user_vote' )} SET pv_actor=(SELECT actor_id FROM {$dbw->tableName( 'actor' )} WHERE actor_user=pv_user_id AND actor_name=pv_user_name)",
			__METHOD__
		);
		return true;
	}
}

$maintClass = MigrateOldPollNYUserColumnsToActor::class;
require_once RUN_MAINTENANCE_IF_MAIN;
