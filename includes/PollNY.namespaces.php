<?php
/**
 * Translations of the Poll namespace.
 *
 * @file
 */

$namespaceNames = [];

// For wikis where the PollNY extension is not installed.
if ( !defined( 'NS_POLL' ) ) {
	define( 'NS_POLL', 300 );
}

if ( !defined( 'NS_POLL_TALK' ) ) {
	define( 'NS_POLL_TALK', 301 );
}

/** English */
$namespaceNames['en'] = [
	NS_POLL => 'Poll',
	NS_POLL_TALK => 'Poll_talk',
];

/** Finnish (Suomi) */
$namespaceNames['fi'] = [
	NS_POLL => 'Äänestys',
	NS_POLL_TALK => 'Keskustelu_äänestyksestä',
];

/** Dutch (Nederlands) */
$namespaceNames['nl'] = [
	NS_POLL => 'Peiling',
	NS_POLL_TALK => 'Overleg_peiling',
];
