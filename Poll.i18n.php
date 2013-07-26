<?php
/**
 * Internationalization file for the Poll extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 */
$messages['en'] = array(
	'adminpoll' => 'Administrate polls',
	'createpoll' => 'Create a poll',
	'randompoll' => 'Random poll',
	'viewpoll' => 'View polls',
	'poll-desc' => 'Advanced polling system that uses special pages and allows embedding polls to wiki pages',
	'poll-admin-no-polls' => 'There are no polls. [[Special:CreatePoll|Create a poll!]]',
	'poll-admin-closed' => 'Closed',
	'poll-admin-flagged' => 'Flagged',
	'poll-admin-open' => 'Open',
	'poll-admin-panel' => 'Admin',
	'poll-admin-status-nav' => 'Filter by status',
	'poll-admin-title-all' => 'Poll admin - View all polls',
	'poll-admin-title-closed' => 'Poll admin - View closed polls',
	'poll-admin-title-flagged' => 'Poll admin - View flagged polls',
	'poll-admin-title-open' => 'Poll admin - view open polls',
	'poll-admin-viewall' => 'View all',
	'poll-ago' => '$1 ago',
	'poll-atleast' => 'You must have at least two answer choices.',
	'poll-based-on-votes' => 'based on {{PLURAL:$1|one vote|$1 votes}}',
	'poll-cancel-button' => 'Cancel',
	'poll-category' => 'Polls',
	'poll-category-user' => 'Polls by user $1',
	'poll-choices-label' => 'Answer choices',
	'poll-close-message' => 'Are you sure you want to close this poll? All voting will be suspended.',
	'poll-close-poll' => 'Close poll',
	'poll-closed' => 'This poll has been closed for voting.',
	'poll-create' => 'Create poll',
	'poll-createpoll-error-nomore' => 'There are no more polls left!', // keep it simple, this is used in JS so it cannot contain links etc.
	'poll-create-button' => 'Create and play!',
	'poll-create-threshold-reason' => 'Sorry, you cannot create a poll until you have at least $1.',
	'poll-create-threshold-title' => 'Create poll',
	'poll-create-title' => 'Create a poll',
	'poll-createdago' => 'Created $1 ago',
	'poll-delete-message' => 'Are you sure you want to delete this poll?',
	'poll-delete-poll' => 'Delete poll',
	'poll-discuss' => 'Discuss',
	'poll-edit-answers' => 'Edit answers',
	'poll-edit-button' => 'Save page',
	'poll-edit-desc' => 'new poll',
	'poll-edit-image' => 'Edit image',
	'poll-edit-invalid-access' => 'Invalid access',
	'poll-edit-title' => 'Editing poll - $1',
	'poll-embed' => 'Embed on wiki page',
	'poll-enterquestion' => 'You must enter a question.',
	'poll-finished' => '<b>There are no more polls left. Add your <a href="$1">own!</a></b> Or <a href="$2">view results of this poll</a>',
	'poll-flagged' => 'This poll has been flagged',
	'poll-flagged-message' => 'Are you sure you want to flag this poll?',
	'poll-flag-poll' => 'Flag poll',
	'poll-hash' => '# is an invalid character for the poll question.',
	'poll-image-label' => 'Add an image',
	'poll-instructions' => 'Ask a poll question, write some answer choices, press the "{{int:poll-create-button}}" button. It\'s that easy!',
	'poll-js-action-complete' => 'Action has been completed.',
	'poll-js-loading' => 'Loading...',
	'poll-next' => 'next',
	'poll-next-poll' => 'Next poll',
	'poll-no-more-message' => 'You have voted for every poll!<br />Don\'t get sad, [[Special:CreatePoll|create your very own]]!',
	'poll-no-more-title' => 'No more polls!',
	'poll-open-message' => 'Are you sure you want to open this poll?',
	'poll-open-poll' => 'Open poll',
	'poll-pleasechoose' => 'Please choose another poll name.',
	'poll-prev' => 'prev',
	'poll-previous-poll' => 'Previous poll',
	'poll-question-label' => 'Poll question',
	'poll-skip' => 'Skip >',
	'poll-submitted-by' => 'Submitted by',
	'poll-take-button' => '< Back to polls',
	'poll-unavailable' => 'This poll is unavailable',
	'poll-unflag-poll' => 'Unflag poll',
	'poll-upload-image-button' => 'Upload',
	'poll-upload-new-image' => 'Upload new image',
	'poll-view-title' => "View $1's polls",
	'poll-view-order' => 'Order',
	'poll-view-newest' => 'Newest',
	'poll-view-popular' => 'Popular',
	'poll-view-answered-times' => 'Answered {{PLURAL:$1|one time|$1 times}}',
	'poll-view-all-by' => 'View all polls by $1',
	'poll-voted-for' => 'You have voted for $1 {{PLURAL:$1|poll|polls}} out of <b>$2</b> total polls and received <span class="profile-on">$3 points</span>.',
	'poll-votes' => '{{PLURAL:$1|one vote|$1 votes}}',
	'poll-woops' => 'Woops!',
	'poll-would-have-earned' => 'You would have earned <span class="profile-on">$1 points</span> had you [[Special:UserLogin/signup|signed up]].',
	'poll-time-ago' => '$1 ago',
	'poll-time-days' => '{{PLURAL:$1|one day|$1 days}}',
	'poll-time-hours' => '{{PLURAL:$1|one hour|$1 hours}}',
	'poll-time-minutes' => '{{PLURAL:$1|one minute|$1 minutes}}',
	'poll-time-seconds' => '{{PLURAL:$1|one second|$1 seconds}}',
	'specialpages-group-poll' => 'Polls',
	'right-polladmin' => 'Administer polls',
);

/** Message documentation (Message documentation)
 * @author Siebrand
 */
$messages['qqq'] = array(
	'adminpoll' => 'Title of Special:AdminPoll, the special page to administrate polls. Shown on Special:SpecialPages, etc.',
	'createpoll' => 'Title of Special:CreatePoll, the special page to create a new poll. Shown on Special:SpecialPages, etc.',
	'randompoll' => "Title of Special:RandomPoll, which takes you to a poll that you haven't voted in yet. Shown on Special:SpecialPages.",
	'viewpoll' => 'Title of Special:ViewPoll, which allows to view all available polls. Shown on Special:SpecialPages, etc.',
	'poll-admin-no-polls' => 'Message displayed on Special:AdminPoll and Special:ViewPoll when no polls match the supplied criteria or when there are absolutely no polls at all in the database.',
	'poll-admin-closed' => 'Link title; clicking on this link shows all closed polls',
	'poll-admin-flagged' => 'Link title; clicking on this link shows all flagged polls',
	'poll-admin-open' => 'Link title; clicking on this link shows all open polls',
	'poll-admin-panel' => 'Title of the admin panel link; shown on all Poll: pages, below the box {{msg-mw|poll-submitted-by}}.',
	'poll-admin-status-nav' => 'Title of the right-hand navigation menu on Special:AdminPoll',
	'poll-admin-title-all' => 'Default title of Special:AdminPoll',
	'poll-admin-title-closed' => 'Title of Special:AdminPoll when viewing closed polls (status=closed in the URL)',
	'poll-admin-title-flagged' => 'Title of Special:AdminPoll when viewing flagged polls (status=flagged in the URL)',
	'poll-admin-title-open' => 'Title of Special:AdminPoll when viewing open polls (status=open in the URL)',
	'poll-admin-viewall' => 'Link title; displayed in the right-hand navigation menu on Special:AdminPolls. Clicking on this link displays all polls in the database (as opposed to only displaying closed/open/flagged polls).',
	'poll-ago' => 'Used on Special:ViewPoll. $1 is one of the following:
* [[MediaWiki:Poll-time-days]]
* [[MediaWiki:Poll-time-hours]]
* [[MediaWiki:Poll-time-minutes]]
* [[MediaWiki:Poll-time-seconds]]',
	'poll-atleast' => 'Error message shown in a JavaScript <code>alert()</code> on Special:CreatePoll after the user has pressed the {{msg-mw|poll-create-button}} button if there is only one answer choice.',
	'poll-based-on-votes' => 'Shown on the output of a poll embedded via the <code>&lt;pollembed&gt;</code> tag',
	'poll-cancel-button' => 'Button label',
	'poll-category' => "Name of the category where '''all''' Poll: pages will be stored",
	'poll-category-user' => "Name of the category where '''all''' Poll: pages (polls) created by the user will be stored; $1 is thus a username",
	'poll-choices-label' => 'Shown on Special:CreatePoll; the input elements for entering the answer choices are shown below this text',
	'poll-close-message' => 'Confirmation message shown to the user (who has the "polladmin" user right) in a JavaScript <code>alert()</code> after they\'ve pressed the {{int:poll-close-poll}} link to close a poll.',
	'poll-close-poll' => 'Link text; closing a poll prevents users from voting in it',
	'poll-closed' => 'Informational message displayed on a page where a poll has been embedded via the <code>&lt;pollembed&gt;</code> tag and said poll has since been closed for voting, i.e. no new votes are accepted',
	'poll-create' => 'Link title; this link is shown on the right-hand "{{int:poll-submitted-by}}" box on all Poll: pages',
	'poll-createpoll-error-nomore' => 'Error message; keep it simple, this is used in JavaScript so it cannot contain links etc.',
	'poll-create-button' => 'Button text',
	'poll-create-threshold-reason' => 'Error message displayed when <code>$wgCreatePollThresholds</code> is defined and the user does not match one or more of the defined thresholds. $1 could be something like "5 edits".',
	'poll-create-threshold-title' => 'Page title for Special:CreatePoll when <code>$wgCreatePollThresholds</code> is defined and the user cannot create a new poll due to not meeting the set threshold(s); similar to [[MediaWiki:Poll-create-title]]',
	'poll-create-title' => 'Title of Special:CreatePoll, which allows creating new polls',
	'poll-createdago' => 'Displayed at the bottom of every Poll: page as well as at the bottom of polls embedded via the <code>&lt;pollembed&gt;</code> tag. $1 is a timestamp, like "one hour and 15 minutes"',
	'poll-delete-message' => 'Confirmation message shown to the user (who has the "polladmin" user right) in a JavaScript <code>alert()</code> after they\'ve pressed the {{int:poll-delete-poll}} link to delete a poll.',
	'poll-delete-poll' => 'Link title; this link is shown below the right-hand "{{int:poll-submitted-by}}" box on all Poll: pages for users who have the "polladmin" user right',
	'poll-discuss' => 'Link title; this link is shown below the [[MediaWiki:Poll-based-on-votes|based on X vote(s)]] text when a poll has been embedded on a wiki page via the <code>&lt;pollembed&gt;</code> tag',
	'poll-edit-answers' => 'Shown on Special:UpdatePoll; the answer boxes are displayed below this text on the left-hand side of the page',
	'poll-edit-button' => 'Button label shown on Special:UpdatePoll; pressing this button saves all changes (both those to the answers as well as if the picture was changed)',
	'poll-edit-desc' => 'Edit summary for the edit when a new poll is created via Special:CreatePoll',
	'poll-edit-image' => 'Heading on Special:UpdatePoll',
	'poll-edit-invalid-access' => "Error message shown on Special:UpdatePoll if a poll ID wasn't supplied or the user trying to edit the poll is neither a poll admin nor the poll's original creator.",
	'poll-edit-title' => 'Displayed on Special:UpdatePoll as the page title. $1 is the name of the poll (the poll question without the Poll: namespace or its equivalent localization)',
	'poll-embed' => 'Shown on all Poll: pages; this is followed by an input that contains code for embedding the poll in question to a normal wiki page',
	'poll-enterquestion' => 'Shown in a JavaScript <code>alert()</code> on Special:CreatePoll if the user has pressed the {{int:poll-create-button}} button without entering a question',
	'poll-finished' => 'Shown (via JavaScript) when the user has voted in all available polls. $1 is the URL to Special:CreatePoll, $2 is the URL to the current page.',
	'poll-flagged' => 'Informational message displayed on the Poll: page of a poll that has been flagged by someone and thus is not accepting any new votes at the moment',
	'poll-flagged-message' => "Displayed to the user in a JavaScript <code>alert()</code> after they've pressed the {{int:poll-flag-poll}} link on a Poll: page.",
	'poll-flag-poll' => 'Link title; flagging removes the poll from circulation until an admin has reviewed and either reapproved or deleted it.',
	'poll-hash' => "Displayed to the user in a JavaScript <code>alert()</code> if they try to create a poll where the question contains the # (hash) character after they've pressed the {{int:poll-create-button}} button.",
	'poll-image-label' => 'Heading title on Special:CreatePoll; the upload form for uploading a picture to the poll is displayed below this header',
	'poll-instructions' => 'Instructions displayed to the user on Special:CreatePoll, below the "{{int:poll-create-title}}" title',
	'poll-js-action-complete' => "Displayed (via JavaScript) to the user when they've performed an administrative action, such as closing a poll.",
	'poll-js-loading' => 'Shown via JavaScript to users using the Firefox browser on a Mac instead of the Flash animation at /extensions/PollNY/ajax-loading.swf',
	'poll-next' => 'Pagination link; abbreviation of the word "next". Keep this message short!',
	'poll-next-poll' => "Link title; shown on Poll: pages if there's a next poll available",
	'poll-no-more-message' => "Error-ish message displayed to the user when they've voted for all available polls",
	'poll-no-more-title' => 'Title of the (error) page displayed when there are no (more) polls to participate in',
	'poll-open-message' => "Displayed to the user in a JavaScript <code>alert()</code> after they've pressed the {{int:poll-open-poll}} link on a Poll: page.",
	'poll-open-poll' => 'Link text; opening a poll allows users to vote in it',
	'poll-pleasechoose' => "Error message displayed to the user in a JavaScript <code>alert()</code> after the user has pressed the {{int:poll-create-bytton}} button to attempt to create a new poll if the new poll's title fails validation (i.e. if there already exists a poll with the exact same title)",
	'poll-prev' => 'Pagination link; abbreviation of the word "previous". Keep this message short!',
	'poll-previous-poll' => 'Link title; shown on Poll: pages if there is a previous poll available',
	'poll-question-label' => 'Shown on Special:CreatePoll; the input element for entering the question is shown below this text',
	'poll-skip' => 'Link title; shown on the bottom of each Poll: page. Clicking on this link allows the user to skip voting in the current poll and bring up a new poll.',
	'poll-submitted-by' => "Title of the box shown on each Poll: page that contains the poll author's username, avatar, some statistics about the poll author and a link to all the polls created by that user.",
	'poll-take-button' => 'Link title, shown on both Special:AdminPoll and Special:ViewPoll; on the latter page, clicking on this link takes the user to a random poll via Special:RandomPoll',
	'poll-unavailable' => "Message displayed when a poll embedded to a page with the <code>&lt;pollembed&gt;</code> tag cannot be rendered (the poll page's page ID is zero or less)",
	'poll-unflag-poll' => 'Link text; unflagging a poll allows the poll to be shown to users so that they can participate in it',
	'poll-upload-image-button' => 'Button text',
	'poll-upload-new-image' => "Link title. This link is shown on Special:CreatePoll after the user has uploaded an image with that special page's built-in upload form.",
	'poll-view-title' => "$1 is a username; this message can be the title of Special:ViewPoll when viewing the polls of a certain user (as opposed to viewing all available polls, in which case that special page's title is [[MediaWiki:Viewpoll]])",
	'poll-view-order' => 'Heading of the right-hand navigation menu on Special:ViewPoll; "order" refers to the sort order',
	'poll-view-newest' => 'Link title on the right-hand navigation menu on Special:ViewPoll',
	'poll-view-popular' => 'the right-hand navigation menu on Special:ViewPoll;',
	'poll-view-answered-times' => 'Shown on Special:AdminPoll below the last answer option of each poll; $1 is the number indicating how many times the poll has been answered',
	'poll-view-all-by' => "Link text shown in the {{int:poll-submitted-by}} box, below the user's avatar. $1 is a username (truncated if it's over 27 characters long).",
	'poll-voted-for' => "Message shown to registered users after they've voted in a poll. $1 is the amount of polls in which the current user has voted, $2 is the amount of all polls in the database and $3 is the total amount of points the user has received from voting in polls",
	'poll-votes' => 'Amount of votes, displayed next to or below the bar indicating vote perentages (when displaying a poll on a wiki page via the <code>&lt;pollembed&gt;</code> tag',
	'poll-woops' => 'Error message title',
	'poll-would-have-earned' => "Message shown to anonymous users after they've voted in a poll, prompting them to join the wiki. Registered users are shown the [[MediaWiki:Poll-voted-for]] message instead.",
	'poll-time-ago' => '$1 is one of the following:
* [[MediaWiki:Poll-time-days]]
* [[MediaWiki:Poll-time-hours]]
* [[MediaWiki:Poll-time-minutes]]
* [[MediaWiki:Poll-time-seconds]]',
	'specialpages-group-poll' => 'Special page group title, shown on Special:SpecialPages',
	'right-polladmin' => 'Description of the "polladmin" user right, shown on Special:ListGroupRights',
);

/** German (Deutsch)
 * @author Metalhead64
 */
$messages['de'] = array(
	'adminpoll' => 'Abstimmungen verwalten',
	'createpoll' => 'Eine Abstimmung erstellen',
	'randompoll' => 'Zufällige Abstimmung',
	'viewpoll' => 'Abstimmungen ansehen',
	'poll-desc' => 'Erweitertes Abstimmungssystem, das Spezialseiten nutzt und das Einbinden von Abstimmungen in Wikiseiten ermöglicht',
	'poll-admin-no-polls' => 'Es gibt keine Abstimmungen. [[Special:CreatePoll|Erstelle eine Abstimmung!]]',
	'poll-admin-closed' => 'Geschlossen',
	'poll-admin-flagged' => 'Markiert',
	'poll-admin-open' => 'Offen',
	'poll-admin-panel' => 'Verwalten',
	'poll-admin-status-nav' => 'Nach Status filtern',
	'poll-admin-title-all' => 'Abstimmungsverwaltung – Alle Abstimmungen ansehen',
	'poll-admin-title-closed' => 'Abstimmungsverwaltung – Geschlossene Abstimmungen ansehen',
	'poll-admin-title-flagged' => 'Abstimmungsverwaltung – Markierte Abstimmungen ansehen',
	'poll-admin-title-open' => 'Abstimmungsverwaltung – Offene Abstimmungen ansehen',
	'poll-admin-viewall' => 'Alle ansehen',
	'poll-ago' => 'vor $1',
	'poll-atleast' => 'Du musst mindestens zwei Antwortmöglichkeiten haben',
	'poll-based-on-votes' => 'basierend auf {{PLURAL:$1|einer Abstimmung|$1 Abstimmungen}}',
	'poll-cancel-button' => 'Abbrechen',
	'poll-category' => 'Abstimmungen',
	'poll-category-user' => 'Abstimmungen von $1',
	'poll-choices-label' => 'Antwortmöglichkeiten',
	'poll-close-message' => 'Willst du diese Abstimmung wirklich schließen? Alle Abstimmungen werden ausgesetzt.',
	'poll-close-poll' => 'Abstimmung schließen',
	'poll-closed' => 'Diese Abstimmung wurde geschlossen',
	'poll-create' => 'Abstimmung erstellen',
	'poll-createpoll-error-nomore' => 'Es gibt keine Abstimmungen mehr!',
	'poll-create-button' => 'Erstellen und spielen!',
	'poll-create-threshold-reason' => 'Leider kannst du keine Abstimmung erstellen, bis du mindestens $1 hast.',
	'poll-create-threshold-title' => 'Abstimmung erstellen',
	'poll-create-title' => 'Eine Abstimmung erstellen',
	'poll-createdago' => 'Vor $1 erstellt',
	'poll-delete-message' => 'Willst du diese Abstimmung wirklich löschen?',
	'poll-delete-poll' => 'Abstimmung löschen',
	'poll-discuss' => 'Diskutieren',
	'poll-edit-answers' => 'Antworten bearbeiten',
	'poll-edit-button' => 'Seite speichern',
	'poll-edit-desc' => 'neue Abstimmung',
	'poll-edit-image' => 'Bild bearbeiten',
	'poll-edit-invalid-access' => 'Ungültiger Zugriff',
	'poll-edit-title' => 'Abstimmung bearbeiten – $1',
	'poll-embed' => 'In Wikiseite einbinden',
	'poll-enterquestion' => 'Du musst eine Frage eingeben',
	'poll-finished' => '<b>Es gibt keine Abstimmungen mehr. Erstelle deine <a href="$1">eigene</a></b> oder <a href="$2">sieh dir die Ergebnisse dieser Abstimmung an</a>.',
	'poll-flagged' => 'Diese Abstimmung wurde markiert',
	'poll-flagged-message' => 'Willst du diese Abstimmung wirklich markieren?',
	'poll-flag-poll' => 'Abstimmung markieren',
	'poll-hash' => '# ist ein ungültiges Zeichen für die Abstimmungsfrage.',
	'poll-image-label' => 'Ein Bild hinzufügen',
	'poll-instructions' => 'Stelle eine Abstimmungsfrage, biete einige Antwortmöglichkeiten an und klicke auf „{{int:poll-create-button}}“. Es ist so einfach!',
	'poll-js-action-complete' => 'Aktion vollständig',
	'poll-js-loading' => 'Lade …',
	'poll-next' => 'nächste',
	'poll-next-poll' => 'Nächste Abstimmung',
	'poll-no-more-message' => 'Du hast für jede Abstimmung gestimmt!<br />Sei nicht traurig, [[Special:CreatePoll|erstelle deine eigene]]!',
	'poll-no-more-title' => 'Keine weiteren Abstimmungen!',
	'poll-open-message' => 'Willst du diese Abstimmung wirklich eröffnen?',
	'poll-open-poll' => 'Offene Abstimmung',
	'poll-pleasechoose' => 'Bitte wähle einen anderen Abstimmungsnamen',
	'poll-prev' => 'vorherige',
	'poll-previous-poll' => 'Vorherige Abstimmung',
	'poll-question-label' => 'Abstimmungsfrage',
	'poll-skip' => 'Überspringen >',
	'poll-submitted-by' => 'Erstellt von',
	'poll-take-button' => '< Zurück zu den Abstimmungen',
	'poll-unavailable' => 'Diese Abstimmung ist nicht verfügbar',
	'poll-unflag-poll' => 'Markierung der Abstimmung aufheben',
	'poll-upload-image-button' => 'Hochladen',
	'poll-upload-new-image' => 'Neues Bild hochladen',
	'poll-view-title' => 'Abstimmungen von $1 ansehen',
	'poll-view-order' => 'Reihenfolge',
	'poll-view-newest' => 'Neueste',
	'poll-view-popular' => 'Beliebt',
	'poll-view-answered-times' => '{{PLURAL:$1|Einmal|$1 mal}} geantwortet',
	'poll-view-all-by' => 'Alle Abstimmungen von $1 ansehen',
	'poll-voted-for' => 'Du hast für {{PLURAL:$1|eine Abstimmung|$1 Abstimmungen}} gestimmt von insgesamt <b>$2</b> und hast <span class="profile-on">$3 Punkte</span> erhalten',
	'poll-votes' => '{{PLURAL:$1|eine Abstimmung|$1 Abstimmungen}}',
	'poll-woops' => 'Hoppla!',
	'poll-would-have-earned' => 'Du hättest <span class="profile-on">$1 Punkte</span> verdient, wenn du dich [[Special:UserLogin/signup|registriert hättest]].',
	'poll-time-ago' => 'vor $1',
	'poll-time-days' => '{{PLURAL:$1|einem Tag|$1 Tagen}}',
	'poll-time-hours' => '{{PLURAL:$1|einer Stunde|$1 Stunden}}',
	'poll-time-minutes' => '{{PLURAL:$1|einer Minute|$1 Minuten}}',
	'poll-time-seconds' => '{{PLURAL:$1|einer Sekunde|$1 Sekunden}}',
	'specialpages-group-poll' => 'Abstimmungen',
	'right-polladmin' => 'Abstimmungen verwalten',
);

/** Finnish (suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'adminpoll' => 'Hallinnoi äänestyksiä',
	'createpoll' => 'Luo äänestys',
	'randompoll' => 'Satunnainen äänestys',
	'viewpoll' => 'Katso äänestyksiä',
	'poll-admin-no-polls' => 'Äänestyksiä ei ole olemassa. [[Special:CreatePoll|Luo äänestys!]]',
	'poll-admin-closed' => 'Suljetut',
	'poll-admin-flagged' => 'Merkityt',
	'poll-admin-open' => 'Avoimet',
	'poll-admin-panel' => 'Halinnoi',
	'poll-admin-status-nav' => 'Suodata tilan mukaan',
	'poll-admin-title-all' => 'Äänestysten ylläpito - katso kaikki äänestykset',
	'poll-admin-title-closed' => 'Äänestysten ylläpito - katso suljetut äänestykset',
	'poll-admin-title-flagged' => 'Äänestysten ylläpito - katso merkityt äänestykset',
	'poll-admin-title-open' => 'Äänestysten ylläpito - katso avoimet äänestykset',
	'poll-admin-viewall' => 'Katso kaikki',
	'poll-ago' => '$1 sitten',
	'poll-atleast' => 'Sinulla täytyy olla ainakin kaksi vastausvaihtoehtoa',
	'poll-based-on-votes' => 'perustuen {{PLURAL:$1|yhteen ääneen|$1 ääneen}}',
	'poll-cancel-button' => 'Peruuta',
	'poll-category' => 'Äänestykset',
	'poll-category-user' => 'Käyttäjän $1 äänestykset',
	'poll-choices-label' => 'Vastausvaihtoehdot',
	'poll-close-message' => 'Oletko varma, että haluat sulkea tämän äänestyksen? Kaikkien äänien antaminen keskeytetään.',
	'poll-close-poll' => 'Sulje äänestys',
	'poll-closed' => 'Tämä äänestys on suljettu ääniltä',
	'poll-create' => 'Luo äänestys',
	'poll-createpoll-error-nomore' => 'Äänestyksiä ei ole enempää jäljellä!',
	'poll-create-button' => 'Luo ja pelaa!',
	'poll-create-threshold-reason' => 'Pahoittelut, et voi luoda äänestystä ennen kuin sinulla on ainakin $1',
	'poll-create-threshold-title' => 'Luo äänestys',
	'poll-create-title' => 'Luo äänestys',
	'poll-createdago' => 'Luotu $1 sitten',
	'poll-delete-message' => 'Oletko varma, että haluat poistaa tämän äänestyksen?',
	'poll-delete-poll' => 'Poista äänestys',
	'poll-discuss' => 'Keskustele',
	'poll-edit-answers' => 'Muokkaa vastauksia',
	'poll-edit-button' => 'Tallenna sivu',
	'poll-edit-desc' => 'uusi äänestys',
	'poll-edit-image' => 'Muokkaa kuvaa',
	'poll-edit-title' => 'Muokataan äänestystä - $1',
	'poll-embed' => 'Upota wiki-sivulle',
	'poll-enterquestion' => 'Sinun tulee antaa kysymys',
	'poll-finished' => '<b>Äänestyksiä ei ole enempää jäljellä. Lisää <a href="$1">omasi</a></b> tai <a href="$2">katso tämän äänestyksen tulokset</a>',
	'poll-flagged' => 'Tämä äänestys on merkitty',
	'poll-flagged-message' => 'Oletko varma, että haluat merkitä tämän äänestyksen?',
	'poll-flag-poll' => 'Merkitse äänestys',
	'poll-hash' => '# on merkki, joka ei kelpaa äänestyksen kysymykseen.',
	'poll-image-label' => 'Lisää kuva',
	'poll-instructions' => 'Kysy kysymys, kirjoita joitakin vastausvaihtoehtoja ja paina "{{int:poll-create-button}}" -painiketta. Se on niin helppoa!',
	'poll-js-action-complete' => 'Toiminto suoritettu',
	'poll-js-loading' => 'Ladataan...',
	'poll-next' => 'seur.',
	'poll-next-poll' => 'Seuraava äänestys',
	'poll-no-more-message' => 'Olet äänestänyt jokaiseen äänestykseen!<br />Älä masennu, [[Special:CreatePoll|luo omasi]]!',
	'poll-no-more-title' => 'Ei enempää äänestyksiä!',
	'poll-open-message' => 'Oletko varma, että haluat avata tämän äänestyksen?',
	'poll-open-poll' => 'Avaa äänestys',
	'poll-pleasechoose' => 'Ole hyvä ja valitse toinen nimi äänestykselle',
	'poll-prev' => 'edell.',
	'poll-previous-poll' => 'Edellinen äänestys',
	'poll-question-label' => 'Äänestyskysymys',
	'poll-skip' => 'Ohita >',
	'poll-submitted-by' => 'Lähettänyt',
	'poll-take-button' => '&lt; Takaisin äänestyksiin',
	'poll-unavailable' => 'Tämä äänestys ei ole saatavilla',
	'poll-unflag-poll' => 'Poista merkintä äänestyksestä',
	'poll-upload-image-button' => 'Tallenna',
	'poll-upload-new-image' => 'Tallenna uusi kuva',
	'poll-view-title' => 'Katso käyttäjän $1 äänestykset',
	'poll-view-order' => 'Järjestys',
	'poll-view-newest' => 'Uusimmat',
	'poll-view-popular' => 'Suositut',
	'poll-view-answered-times' => 'Vastattu {{PLURAL:$1|kerran|$1 kertaa}}',
	'poll-view-all-by' => 'Katso kaikki käyttäjän $1 äänestykset',
	'poll-voted-for' => 'Olet äänestänyt {{PLURAL:$1|yhteen äänestykseen|$1 äänestykseen}} kaikista äänestyksistä, joita on yhteensä <b>$2</b> kappaletta ja saanut <span class="profile-on">$3 pistettä</span>',
	'poll-votes' => '{{PLURAL:$1|yksi ääni|$1 ääntä}}',
	'poll-woops' => 'Ups!',
	'poll-would-have-earned' => 'Olisit ansainnut <span class="profile-on">$1 pistettä</span> jos olisit [[Special:UserLogin/signup|rekisteröitynyt]]',
	'poll-time-ago' => '$1 sitten',
	'poll-time-days' => '{{PLURAL:$1|yksi päivä|$1 päivää}}',
	'poll-time-hours' => '{{PLURAL:$1|yksi tunti|$1 tuntia}}',
	'poll-time-minutes' => '{{PLURAL:$1|yksi minuutti|$1 minuuttia}}',
	'poll-time-seconds' => '{{PLURAL:$1|yksi sekunti|$1 sekuntia}}',
	'specialpages-group-poll' => 'Äänestykset',
	'right-polladmin' => 'Hallinnoida äänestyksiä',
);

/** French (français)
 * @author Constant Depièreux
 */
$messages['fr'] = array(
	'adminpoll' => 'Administration des sondages',
	'createpoll' => 'Créer un sondage',
	'randompoll' => 'Sondages aléatoires',
	'viewpoll' => 'Voir les sondages',
	'poll-admin-no-polls' => "Il n'y a pas de sondage. [[Special:CreatePoll|Créer un sondage!]]",
	'poll-admin-closed' => 'Fermé',
	'poll-admin-flagged' => 'Marqué',
	'poll-admin-open' => 'Ouvrir',
	'poll-admin-panel' => 'Admininistrer',
	'poll-admin-status-nav' => 'Filtrer par statut',
	'poll-admin-title-all' => 'Adminstration des sondages - Voir tous les sondages',
	'poll-admin-title-closed' => 'Adminstration des sondages - Voir les sondages clôturés',
	'poll-admin-title-flagged' => 'Adminstration des sondages - Voir les sondages marqués',
	'poll-admin-title-open' => 'Adminstration des sondages - Voir les sondages ouverts',
	'poll-admin-viewall' => 'Tout visualiser',
	'poll-ago' => '$1 passé',
	'poll-atleast' => 'Vous devez avoir au moins deux réponses possibles',
	'poll-based-on-votes' => 'basé sur {{PLURAL:$1|un vote|$1 votes}}',
	'poll-cancel-button' => 'Supprimer',
	'poll-category' => 'Sondages',
	'poll-category-user' => 'Sondage par utilisateur $1',
	'poll-choices-label' => 'Réponses possibles',
	'poll-close-message' => 'Etes-vous sûr que vous voulez clôturer ce sondage? Tous les votes futurs seront suspendus.',
	'poll-close-poll' => 'Clôturer le sondage',
	'poll-closed' => 'Ce sondage est clos. Tous les votes sont terminés',
	'poll-create' => 'Créer un sondage',
	'poll-createpoll-error-nomore' => "Il n'y a plus de sondage ouvert!",
	'poll-create-button' => 'Créez et jouez!',
	'poll-create-threshold-reason' => "Désolé, vous ne pouvez créer de sondage avant d'avoir $1",
	'poll-create-threshold-title' => 'Créer un sondage',
	'poll-create-title' => 'Créer un sondage',
	'poll-createdago' => 'Créé il y a $1 passé',
	'poll-delete-message' => 'Etes-vous sûr que vous vouler supprimer ce sondage?',
	'poll-delete-poll' => 'Supprimer un sondage',
	'poll-discuss' => 'Discussions',
	'poll-edit-answers' => 'Edition des réponses',
	'poll-edit-button' => 'Sauvegarde de la page',
	'poll-edit-desc' => 'nouveau sondage',
	'poll-edit-image' => "Edition de l'image",
	'poll-edit-invalid-access' => 'Accès invalide',
	'poll-edit-title' => 'Edition du sondage - $1',
	'poll-embed' => 'Inclusion sur une page wiki',
	'poll-enterquestion' => 'Vous devez entrer une question',
	'poll-finished' => '<b>Il n\'y a plus de sondage. Créez votre <a href="$1">propre sondage</a></b> ou <a href="$2">visualisez le résultat du sondage en cours</a>',
	'poll-flagged' => 'Ce sondage a été marqué',
	'poll-flagged-message' => 'Etes-vous sûr que vous souhaitez marquer ce sondage?',
	'poll-flag-poll' => 'Marquer le sondage',
	'poll-hash' => '# est un caractère invalide.',
	'poll-image-label' => 'Ajouter une image',
	'poll-instructions' => 'Posez une question de sondage, proposez un choix de réponses possibles, pressez le bouton "{{int:poll-create-button}}". N\'est-ce pas facile!',
	'poll-js-loading' => 'En cours de chargement ...',
	'poll-next' => 'suivant',
	'poll-next-poll' => 'Sondage suivant',
	'poll-no-more-message' => 'Vous avez voté pour tous les sondages!<br />Ne soyez pas désolé, [[Special:CreatePoll|créez le vôtre!]]!',
	'poll-no-more-title' => 'Plus de sondage!',
	'poll-open-message' => 'Etes-vous sûr que vous souhaitez ouvrir ce sondage?',
	'poll-open-poll' => 'Sondage ouvert',
	'poll-pleasechoose' => 'Choississez une autre nom pour ce sondage SVP.',
	'poll-prev' => 'précédent',
	'poll-previous-poll' => 'Sondage précédent',
	'poll-question-label' => 'Question du sondage',
	'poll-skip' => 'Aller à >',
	'poll-submitted-by' => 'Proposé par',
	'poll-take-button' => '&lt; Retour aux sondages',
	'poll-unavailable' => 'Ce sondage est indisponible',
	'poll-unflag-poll' => 'Démarquez ce sondage',
	'poll-upload-image-button' => 'Charger',
	'poll-upload-new-image' => 'Cherger une nouvelle image',
	'poll-view-title' => 'Voir le sondage $1',
	'poll-view-order' => 'Ordre',
	'poll-view-newest' => 'Nouveau',
	'poll-view-popular' => 'Populaire',
	'poll-view-answered-times' => 'Répondu {{PLURAL:$1|une fois|$1 fois}}',
	'poll-view-all-by' => 'Voir tous les sondages proposés par $1',
	'poll-voted-for' => 'Vous avez voté pour $1 {{PLURAL:$1|sondage|sondages}} parmi <b>$2</b> sondages possibles et obtenu <span class="profile-on">$3 points</span>',
	'poll-votes' => '{{PLURAL:$1|un vote|$1 votes}}',
	'poll-woops' => 'Oups!',
	'poll-would-have-earned' => 'Vous avez gagné <span class="profile-on">$1 points</span> si vous vous êtes [[Special:UserLogin/signup|identifié]]',
	'poll-time-ago' => '$1 passé',
	'poll-time-days' => '{{PLURAL:$1|un jour|$1 jours}}',
	'poll-time-hours' => '{{PLURAL:$1|une heure|$1 heures}}',
	'poll-time-minutes' => '{{PLURAL:$1|une minute|$1 minutes}}',
	'poll-time-seconds' => '{{PLURAL:$1|une seconde|$1 secondes}}',
	'specialpages-group-poll' => 'Sondages',
	'right-polladmin' => 'Administrer les sondages',
);

/** Dutch (Nederlands)
 * @author Mitchel Corstjens
 * @author Siebrand
 */
$messages['nl'] = array(
	'adminpoll' => 'Peilingen beheren',
	'createpoll' => 'Peiling aanmaken',
	'randompoll' => 'Willekeurige peiling',
	'viewpoll' => 'Peilingen bekijken',
	'poll-admin-no-polls' => 'Er zijn nog geen peilingen. U kunt [[Special:CreatePoll|een peiling aanmaken]].',
	'poll-admin-closed' => 'Gesloten',
	'poll-admin-flagged' => 'Gemarkeerd',
	'poll-admin-open' => 'Open',
	'poll-admin-panel' => 'Beheren',
	'poll-admin-status-nav' => 'Filter op status',
	'poll-admin-title-all' => 'Peilingenbeheer - Alle peilingen bekijken',
	'poll-admin-title-closed' => 'Peilingen beheren - Gesloten peilingen bekijken',
	'poll-admin-title-flagged' => 'Peilingen beheren - Als ongepast gemarkeerde peilingen bekijken',
	'poll-admin-title-open' => 'Peilingen beheren - Open peilingen bekijken',
	'poll-admin-viewall' => 'Alle bekijken',
	'poll-ago' => '$1 geleden',
	'poll-atleast' => 'Geef minimaal twee antwoordmogelijkheden op.',
	'poll-based-on-votes' => 'gebaseerd op {{PLURAL:$1|1 stem|$1 stemmen}}',
	'poll-cancel-button' => 'Annuleren',
	'poll-category' => 'Peilingen',
	'poll-category-user' => 'Peilingen van gebruiker $1',
	'poll-choices-label' => 'Antwoorden',
	'poll-close-message' => 'Weet u zeker dat u deze peiling wilt sluiten? Deelnemen is dan niet meer mogelijk.',
	'poll-close-poll' => 'Peiling sluiten',
	'poll-closed' => 'Deze peiling is gesloten',
	'poll-create' => 'Peiling aanmaken',
	'poll-createpoll-error-nomore' => 'Er zijn geen peilingen meer.',
	'poll-create-button' => 'Maken en gebruiken!',
	'poll-create-threshold-reason' => 'U kunt pas peilingen aanmaken als u $1 hebt.',
	'poll-create-threshold-title' => 'Peiling aanmaken',
	'poll-create-title' => 'Peiling maken',
	'poll-createdago' => '$1 geleden aangemaakt',
	'poll-delete-message' => 'Weet u zeker dat u peiling wilt verwijderen?',
	'poll-delete-poll' => 'Peiling verwijderen',
	'poll-discuss' => 'Overleggen',
	'poll-edit-answers' => 'Antwoorden bewerken',
	'poll-edit-button' => 'Pagina opslaan',
	'poll-edit-desc' => 'nieuwe peiling',
	'poll-edit-image' => 'Afbeelding bewerken',
	'poll-edit-invalid-access' => 'Geen toegang',
	'poll-edit-title' => 'Peiling bewerken - $1',
	'poll-embed' => 'Invoegen op een wikipagina',
	'poll-enterquestion' => 'Geef een vraag op',
	'poll-finished' => '<b>Er zijn geen peilingen meer over. Maak uw <a href="$1">eigen peiling</a></b> of <a href="$2">bekijk resultaten van deze peiling</a>.',
	'poll-flagged' => 'Deze peiling is gemarkeerd als ongepast',
	'poll-flagged-message' => 'Weet je zeker dat u deze peiling als ongepast wilt markeren?',
	'poll-flag-poll' => 'Peiling als ongepast markeren',
	'poll-hash' => '"#" is een ongeldig teken voor de vraag.',
	'poll-image-label' => 'Afbeelding toevoegen',
	'poll-instructions' => 'Stel een vraag, geef een aantal antwoordmogelijkheden en klik op de knop "{{int:poll-create-button}}". Zo makkelijk is het!',
	'poll-js-loading' => 'Bezig met laden…',
	'poll-next' => 'volgende',
	'poll-next-poll' => 'Volgende peiling',
	'poll-no-more-message' => 'U hebt aan alle peilingen deelgenomen.<br />[[Special:CreatePoll|Maak nu uw eigen peiling]]!',
	'poll-no-more-title' => 'Er zijn geen peilingen meer om aan deel te nemen.',
	'poll-open-message' => 'Weet u zeker dat u deze peiling wilt openstellen?',
	'poll-open-poll' => 'Peiling openstellen',
	'poll-pleasechoose' => 'Kies een andere peilingnaam',
	'poll-prev' => 'vorige',
	'poll-previous-poll' => 'Vorige peiling',
	'poll-question-label' => 'Vraag',
	'poll-skip' => 'Overslaan >',
	'poll-submitted-by' => 'Aangemaakt door',
	'poll-take-button' => '< Terug naar peilingen',
	'poll-unavailable' => 'Deze peiling is niet beschikbaar',
	'poll-unflag-poll' => 'Peiling weer openstellen',
	'poll-upload-image-button' => 'Uploaden',
	'poll-upload-new-image' => 'Nieuwe afbeelding toevoegen',
	'poll-view-title' => 'Peilingen van $1 bekijken',
	'poll-view-order' => 'Volgorde',
	'poll-view-newest' => 'Nieuwste',
	'poll-view-popular' => 'Populair',
	'poll-view-answered-times' => '{{PLURAL:$1|$1 keer}} beantwoord',
	'poll-view-all-by' => 'Alle peilingen van $1 bekijken',
	'poll-voted-for' => 'U heeft deelgenomen aan {{PLURAL:$1|$1}} van de <b>{{PLURAL:$2|$2 beschikbare peilingen}}</b> en <span class="profile-on">{{PLURAL:$3|1 punt|$3 punten}}</span> ontvangen.',
	'poll-votes' => '{{PLURAL:$1|1 stem|$1 stemmen}}',
	'poll-woops' => 'Fout',
	'poll-would-have-earned' => 'Als u was [[Special:UserLogin/signup|aangemeld]] had u <span class="profile-on">{{PLURAL:$1|1 punt|$1 punten}}</span> verdiend.',
	'poll-time-ago' => '$1 geleden',
	'poll-time-days' => '{{PLURAL:$1|1 dag|$1 dagen}}',
	'poll-time-hours' => '{{PLURAL:$1|$1 uur}}',
	'poll-time-minutes' => '{{PLURAL:$1|1 minuut|$1 minuten}}',
	'poll-time-seconds' => '{{PLURAL:$1|1 seconde|$1 seconden}}',
	'specialpages-group-poll' => 'Peilingen',
	'right-polladmin' => 'Peilingen beheren',
);
