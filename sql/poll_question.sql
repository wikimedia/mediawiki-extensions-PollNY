CREATE TABLE /*_*/poll_question (
  poll_id int(11) NOT NULL PRIMARY KEY auto_increment,
  poll_page_id int(11) NOT NULL default 0,
  poll_actor bigint unsigned NOT NULL,
  poll_text text NOT NULL,
  poll_image varchar(255) NOT NULL default '',
  poll_status int(5) default 1,
  poll_vote_count int(5) default 0,
  poll_question_vote_count int(5) default 0,
  -- MW standard version for timestamps:
  --`poll_date` binary(14) NOT NULL default '',
  poll_date datetime default NULL,
  poll_random double unsigned default 0
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/poll_actor ON /*_*/poll_question (poll_actor);
CREATE INDEX /*i*/poll_random ON /*_*/poll_question (poll_random);
