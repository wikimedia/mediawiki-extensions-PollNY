<?php

use MediaWiki\MediaWikiServices;

/**
 * Poll class
 */
class Poll {
	// Constants for the poll_question.poll_status field; because nobody likes magic numbers and memorizing them.
	/**
	 * @var int Closed polls cannot be voted on
	 */
	public const STATUS_CLOSED = 0;

	/**
	 * @var int The default status; open means that a poll is available for users
	 */
	public const STATUS_OPEN = 1;

	/**
	 * @var int Flagged means "removed from circulation until an admin has reviewed the poll and taken appropriate action"
	 */
	public const STATUS_FLAGGED = 2;

	/**
	 * Adds a poll question to the database.
	 *
	 * @param $question String: poll question
	 * @param $image String: name of the poll image, if any
	 * @param $pageID Integer: page ID, as returned by Article::getID()
	 * @param User $user relevant user
	 * @return Integer inserted value of an auto-increment row (poll ID)
	 */
	public function addPollQuestion( $question, $image, $pageID, User $user ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'poll_question',
			[
				'poll_page_id' => $pageID,
				'poll_actor' => $user->getActorId(),
				'poll_text' => strip_tags( $question ),
				'poll_image' => $image,
				'poll_date' => date( 'Y-m-d H:i:s' ),
				'poll_random' => wfRandom()
			],
			__METHOD__
		);
		return $dbw->insertId();
	}

	/**
	 * Adds an individual poll answer choice to the database.
	 *
	 * @param $pollID Integer: poll ID number
	 * @param $choiceText String: user-supplied answer choice text
	 * @param $choiceOrder Integer: a value between 1 and 10
	 */
	public function addPollChoice( $pollID, $choiceText, $choiceOrder ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'poll_choice',
			[
				'pc_poll_id' => $pollID,
				'pc_text' => strip_tags( $choiceText ),
				'pc_order' => $choiceOrder
			],
			__METHOD__
		);
	}

	/**
	 * Adds a record to the poll_user_vote table to signify that the user has
	 * already voted.
	 *
	 * @param $pollID Integer: ID number of the poll
	 * @param $choiceID Integer: number of the choice
	 * @param $user relevant user
	 */
	public function addPollVote( $pollID, $choiceID, User $user ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'poll_user_vote',
			[
				'pv_poll_id' => $pollID,
				'pv_pc_id' => $choiceID,
				'pv_actor' => $user->getActorId(),
				'pv_date' => date( 'Y-m-d H:i:s' )
			],
			__METHOD__
		);
		if ( $choiceID > 0 ) {
			$this->incPollVoteCount( $pollID );
			$this->incChoiceVoteCount( $choiceID );
			$stats = new UserStatsTrack( $user->getID(), $user->getName() );
			$stats->incStatField( 'poll_vote' );
		}
	}

	/**
	 * Increases the total amount of votes an answer choice has by one and
	 * commits to DB.
	 *
	 * @param $choiceID Integer: answer choice ID number between 1 and 10
	 */
	public function incChoiceVoteCount( $choiceID ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'poll_choice',
			[ 'pc_vote_count=pc_vote_count+1' ],
			[ 'pc_id' => $choiceID ],
			__METHOD__
		);
	}

	/**
	 * Increases the total amount of votes a poll has by one and commits to DB.
	 *
	 * @param $pollID Integer: poll ID number
	 */
	public function incPollVoteCount( $pollID ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'poll_question',
			[ 'poll_vote_count=poll_vote_count+1' ],
			[ 'poll_id' => $pollID ],
			__METHOD__
		);
	}

	/**
	 * Gets information about a poll.
	 *
	 * @param $pageID Integer: page ID number
	 * @return array Poll information, such as question, choices, status, etc.
	 */
	public function getPoll( $pageID ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'poll_question',
			[
				'poll_text', 'poll_vote_count', 'poll_id', 'poll_status',
				'poll_actor', 'poll_image', 'poll_date'
			],
			[ 'poll_page_id' => $pageID ],
			__METHOD__,
			[ 'OFFSET' => 0, 'LIMIT' => 1 ]
		);
		$row = $dbr->fetchObject( $res );
		$poll = [];
		if ( $row ) {
			$poll['question'] = $row->poll_text;
			$poll['image'] = $row->poll_image;
			$poll['actor'] = $row->poll_actor;
			$poll['votes'] = $row->poll_vote_count;
			$poll['id'] = $row->poll_id;
			$poll['status'] = $row->poll_status;
			$poll['timestamp'] = wfTimestamp( TS_UNIX, $row->poll_date );
			$poll['choices'] = self::getPollChoices( $row->poll_id, $row->poll_vote_count );
		}
		return $poll;
	}

	/**
	 * Gets the answer choices for the poll with ID = $poll_id.
	 *
	 * @param $poll_id Integer: poll ID number
	 * @param $poll_vote_count Integer: 0 by default
	 * @return array[] Poll answer choice info (answer ID, text,
	 * 					amount of votes and percent of total votes)
	 */
	public static function getPollChoices( $poll_id, $poll_vote_count = 0 ) {
		global $wgLang;

		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			'poll_choice',
			[ 'pc_id', 'pc_text', 'pc_vote_count' ],
			[ 'pc_poll_id' => $poll_id ],
			__METHOD__,
			[ 'ORDER BY' => 'pc_order' ]
		);

		$choices = [];
		foreach ( $res as $row ) {
			if ( $poll_vote_count ) {
				$percent = str_replace( '.0', '', $wgLang->formatNum( (int)$row->pc_vote_count / $poll_vote_count * 100, 1 ) );
			} else {
				$percent = 0;
			}
			// $percent = round( $row->pc_vote_count / $poll_vote_count * 100 );

			$choices[] = [
				'id' => $row->pc_id,
				'choice' => $row->pc_text,
				'votes' => $row->pc_vote_count,
				'percent' => $percent
			];
		}

		return $choices;
	}

	/**
	 * Checks if the user has voted already to the poll with ID = $poll_id.
	 *
	 * @param User $user User (object) to check
	 * @param int $poll_id Poll ID number
	 * @return bool True if user has voted, otherwise false
	 */
	public function userVoted( $user, $poll_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$actorId = $user->getActorId();
		$s = $dbr->selectRow(
			'poll_user_vote',
			[ 'pv_id' ],
			[ 'pv_poll_id' => $poll_id, 'pv_actor' => $actorId ],
			__METHOD__
		);
		if ( $s !== false ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if the specified user "owns" the specified poll.
	 *
	 * @param User $user User object to check
	 * @param int $pollId Poll ID number
	 * @return bool True if the user owns the poll, else false
	 */
	public function doesUserOwnPoll( $user, $pollId ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'poll_question',
			[ 'poll_id' ],
			[
				'poll_id' => intval( $pollId ),
				'poll_actor' => intval( $user->getActorId() )
			],
			__METHOD__
		);
		if ( $s !== false ) {
			return true;
		}
		return false;
	}

	/**
	 * Gets the URL of a randomly chosen poll (well, actually just the
	 * namespace and page title).
	 *
	 * @param User $user
	 * @return string Poll namespace name and poll page name or 'error'
	 */
	public function getRandomPollURL( $user ) {
		$pollID = $this->getRandomPollID( $user );
		if ( !$pollID ) {
			return 'error';
		}
		$pollPage = Title::newFromID( $pollID );
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		return $contLang->getNsText( NS_POLL ) . ':' . $pollPage->getDBkey();
	}

	/**
	 * Gets a random poll ID from the database.
	 * The poll ID will be the ID of a poll to which the user hasn't answered
	 * yet.
	 *
	 * @param User $user User (object) for whom to get a random poll
	 * @return int Random poll ID number
	 */
	public function getRandomPollID( $user ) {
		$dbr = wfGetDB( DB_MASTER );
		$poll_page_id = 0;
		$use_index = $dbr->useIndexClause( 'poll_random' );
		$randstr = wfRandom();
		$actorId = (int)$user->getActorId();
		$sql = "SELECT poll_page_id FROM {$dbr->tableName( 'poll_question' )} {$use_index}
			INNER JOIN {$dbr->tableName( 'page' )} ON page_id=poll_page_id WHERE poll_id NOT IN
				(SELECT pv_poll_id FROM {$dbr->tableName( 'poll_user_vote' )} WHERE pv_actor = {$actorId})
				AND poll_status=1 AND poll_random>$randstr ORDER BY poll_random LIMIT 0,1";
		$res = $dbr->query( $sql, __METHOD__ );
		$row = $dbr->fetchObject( $res );
		// random fallback
		if ( !$row ) {
			$sql = "SELECT poll_page_id FROM {$dbr->tableName( 'poll_question' )} {$use_index}
				INNER JOIN {$dbr->tableName( 'page' )} ON page_id=poll_page_id WHERE poll_id NOT IN
					(SELECT pv_poll_id FROM {$dbr->tableName( 'poll_user_vote' )} WHERE pv_actor = {$actorId})
					AND poll_status=1 AND poll_random<$randstr ORDER BY poll_random LIMIT 0,1";
			wfDebugLog( 'PollNY', $sql );
			$res = $dbr->query( $sql, __METHOD__ );
			$row = $dbr->fetchObject( $res );
		}
		if ( $row ) {
			$poll_page_id = $row->poll_page_id;
		}

		return $poll_page_id;
	}

	/**
	 * Updates the status of the poll with the ID $poll_id to $status.
	 *
	 * @param int $pollId Poll ID number
	 * @param int $status 0 (close), 1 (open) or 2 (flag)
	 */
	public function updatePollStatus( $pollId, $status ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'poll_question',
			[ 'poll_status' => $status ],
			[ 'poll_id' => (int)$pollId ],
			__METHOD__
		);
	}

	/**
	 * Gets a list of polls, either from memcached or database, up to $count
	 * polls, ordered by $order and stores the list in cache
	 * (if fetched from DB).
	 *
	 * @param int $count How many polls to fetch? Default is 3.
	 * @param string $order ORDER BY for SQL query, default being 'poll_id'.
	 */
	public static function getPollList( $count = 3, $order = 'poll_id' ) {
		global $wgMemc;

		$polls = [];
		// Try cache
		$key = $wgMemc->makeKey( 'polls', 'order', $order, 'count', $count );
		$data = $wgMemc->get( $key );
		if ( !empty( $data ) && is_array( $data ) ) {
			wfDebug( "Got polls list ($count) ordered by {$order} from cache\n" );
			$polls = $data;
		} else {
			wfDebug( "Got polls list ($count) ordered by {$order} from db\n" );
			$dbr = wfGetDB( DB_REPLICA );
			$params['LIMIT'] = $count;
			$params['ORDER BY'] = "{$order} DESC";
			$res = $dbr->select(
				[ 'poll_question', 'page' ],
				[
					'page_title', 'poll_id', 'poll_vote_count', 'poll_image',
					'poll_date'
				],
				/* WHERE */[ 'poll_status' => 1 ],
				__METHOD__,
				$params,
				[ 'page' => [ 'INNER JOIN', 'page_id = poll_page_id' ] ]
			);
			foreach ( $res as $row ) {
				$polls[] = [
					'title' => $row->page_title,
					'timestamp' => wfTimestamp( TS_UNIX, $row->poll_date ),
					'image' => $row->poll_image,
					'choices' => self::getPollChoices( $row->poll_id, $row->poll_vote_count )
				];
			}
			if ( !empty( $polls ) ) {
				$wgMemc->set( $key, $polls, 60 * 10 );
			}
		}

		return $polls;
	}

	/**
	 * The following three functions are borrowed
	 * from includes/wikia/GlobalFunctionsNY.php
	 */
	public static function dateDiff( $date1, $date2 ) {
		$dtDiff = $date1 - $date2;

		$totalDays = intval( $dtDiff / ( 24 * 60 * 60 ) );
		$totalSecs = $dtDiff - ( $totalDays * 24 * 60 * 60 );
		$dif['w'] = intval( $totalDays / 7 );
		$dif['d'] = $totalDays;
		$dif['h'] = $h = intval( $totalSecs / ( 60 * 60 ) );
		$dif['m'] = $m = intval( ( $totalSecs - ( $h * 60 * 60 ) ) / 60 );
		$dif['s'] = $totalSecs - ( $h * 60 * 60 ) - ( $m * 60 );

		return $dif;
	}

	public static function getTimeOffset( $time, $timeabrv, $timename ) {
		$timeStr = '';
		if ( $time[$timeabrv] > 0 ) {
			// Give grep a chance to find the usages:
			// poll-time-days, poll-time-hours, poll-time-minutes, poll-time-seconds
			$timeStr = wfMessage( "poll-time-{$timename}", $time[$timeabrv] )->parse();
		}
		if ( $timeStr ) {
			$timeStr .= ' ';
		}
		return $timeStr;
	}

	public static function getTimeAgo( $time ) {
		$timeArray = self::dateDiff( time(), $time );
		$timeStrD = self::getTimeOffset( $timeArray, 'd', 'days' );
		$timeStrH = self::getTimeOffset( $timeArray, 'h', 'hours' );
		$timeStrM = self::getTimeOffset( $timeArray, 'm', 'minutes' );
		$timeStrS = self::getTimeOffset( $timeArray, 's', 'seconds' );
		$timeStr = $timeStrD;
		if ( $timeStr < 2 ) {
			$timeStr .= $timeStrH;
			$timeStr .= $timeStrM;
			if ( !$timeStr ) {
				$timeStr .= $timeStrS;
			}
		}
		if ( !$timeStr ) {
			$timeStr = wfMessage( 'poll-time-seconds', 1 )->parse();
		}
		return $timeStr;
	}

}
