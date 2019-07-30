CREATE TABLE /*_*/poll_choice (
  pc_id int(11) NOT NULL PRIMARY KEY auto_increment,
  pc_poll_id int(11) NOT NULL default 0,
  pc_order int(5) default 0,
  pc_text text NOT NULL,
  pc_vote_count int NOT NULL default 0
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/pc_poll_id ON /*_*/poll_choice (pc_poll_id);
