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
	 * @param string $question poll question
	 * @param string $image name of the poll image, if any
	 * @param int $pageID page ID, as returned by Article::getID()
	 * @param User $user relevant user
	 * @return int inserted value of an auto-increment row (poll ID)
	 */
	public function addPollQuestion( $question, $image, $pageID, User $user ) {
		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->insert(
			'poll_question',
			[
				'poll_page_id' => $pageID,
				'poll_actor' => $user->getActorId(),
				'poll_text' => strip_tags( $question ),
				'poll_image' => $image,
				'poll_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
				'poll_random' => wfRandom()
			],
			__METHOD__
		);
		return $dbw->insertId();
	}

	/**
	 * Adds an individual poll answer choice to the database.
	 *
	 * @param int $pollID poll ID number
	 * @param string $choiceText user-supplied answer choice text
	 * @param int $choiceOrder a value between 1 and 10
	 */
	public function addPollChoice( $pollID, $choiceText, $choiceOrder ) {
		$dbw = wfGetDB( DB_PRIMARY );
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
	 * @param int $pollID ID number of the poll
	 * @param int $choiceID number of the choice
	 * @param User $user relevant user
	 */
	public function addPollVote( $pollID, $choiceID, User $user ) {
		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->insert(
			'poll_user_vote',
			[
				'pv_poll_id' => $pollID,
				'pv_pc_id' => $choiceID,
				'pv_actor' => $user->getActorId(),
				'pv_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) )
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
	 * @param int $choiceID answer choice ID number between 1 and 10
	 */
	public function incChoiceVoteCount( $choiceID ) {
		$dbw = wfGetDB( DB_PRIMARY );
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
	 * @param int $pollID poll ID number
	 */
	public function incPollVoteCount( $pollID ) {
		$dbw = wfGetDB( DB_PRIMARY );
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
	 * @param int $pageID page ID number
	 * @return array Poll information, such as question, choices, status, etc.
	 */
	public function getPoll( $pageID ) {
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			'poll_question',
			[
				'poll_text', 'poll_vote_count', 'poll_id', 'poll_status',
				'poll_actor', 'poll_image', 'poll_date'
			],
			[ 'poll_page_id' => $pageID ],
			__METHOD__,
			[ 'OFFSET' => 0, 'LIMIT' => 1 ]
		);
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
	 * @param int $poll_id poll ID number
	 * @param int $poll_vote_count 0 by default
	 * @return array[] Poll answer choice info (answer ID, text,
	 * 					amount of votes and percent of total votes)
	 */
	public static function getPollChoices( $poll_id, $poll_vote_count = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select(
			'poll_choice',
			[ 'pc_id', 'pc_text', 'pc_vote_count' ],
			[ 'pc_poll_id' => $poll_id ],
			__METHOD__,
			[ 'ORDER BY' => 'pc_order' ]
		);

		$choices = [];
		$lang = RequestContext::getMain()->getLanguage();
		foreach ( $res as $row ) {
			if ( $poll_vote_count ) {
				$percent = str_replace( '.0', '', $lang->formatNum(
					(int)$row->pc_vote_count / $poll_vote_count * 100, true ) );
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
				'poll_actor' => $user->getActorId()
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
		$dbr = wfGetDB( DB_PRIMARY );
		$poll_page_id = 0;
		// Note that this is directly embedded as-is into the SQL query below,
		// so be *very* careful when touching this variable!
		$randstr = wfRandom();

		$excludedIds = [];
		$res = $dbr->select(
			'poll_user_vote',
			'pv_poll_id',
			[ 'pv_actor' => $user->getActorId() ],
			__METHOD__
		);
		foreach ( $res as $row ) {
			$excludedIds[] = $row->pv_poll_id;
		}

		$whereConds = [];
		if ( !empty( $excludedIds ) ) {
			$whereConds[] = 'poll_id NOT IN (' . $dbr->makeList( $excludedIds ) . ')';
		}
		$whereConds['poll_status'] = self::STATUS_OPEN;

		$row = $dbr->selectRow(
			'poll_question',
			'poll_page_id',
			$whereConds,
			__METHOD__,
			[
				'ORDER BY' => "ABS(poll_random - $randstr)",
				'LIMIT' => 1
			],
			[ 'page' => [ 'INNER JOIN', 'page_id = poll_page_id' ] ]
		);

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
		$dbw = wfGetDB( DB_PRIMARY );
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
	 * @return array
	 */
	public static function getPollList( $count = 3, $order = 'poll_id' ) {
		$polls = [];
		// Try cache
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'polls', 'order', $order, 'count', $count );
		$data = $cache->get( $key );
		if ( !empty( $data ) && is_array( $data ) ) {
			wfDebug( "Got polls list ($count) ordered by {$order} from cache\n" );
			$polls = $data;
		} else {
			wfDebug( "Got polls list ($count) ordered by {$order} from db\n" );
			$dbr = wfGetDB( DB_REPLICA );
			$params = [];
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
				$cache->set( $key, $polls, 60 * 10 );
			}
		}

		return $polls;
	}

	/**
	 * The following three functions are borrowed
	 * from includes/wikia/GlobalFunctionsNY.php
	 * @param int $date1
	 * @param int $date2
	 * @return array
	 */
	public static function dateDiff( $date1, $date2 ) {
		$dtDiff = $date1 - $date2;

		$totalDays = intval( $dtDiff / ( 24 * 60 * 60 ) );
		$totalSecs = $dtDiff - ( $totalDays * 24 * 60 * 60 );
		$dif = [];
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

	/**
	 * @param int $time poll_timestamp field from the DB
	 * @return string
	 */
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
