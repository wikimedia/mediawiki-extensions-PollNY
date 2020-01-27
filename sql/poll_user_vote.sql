CREATE TABLE /*_*/poll_user_vote (
  pv_id int(11) NOT NULL PRIMARY KEY auto_increment,
  pv_poll_id int(11) NOT NULL default 0,
  pv_pc_id int(5) default 0,
  pv_actor bigint unsigned NOT NULL,
  pv_date datetime default NULL
  -- MW standard version for timestamps:
  --`pv_date` binary(14) NOT NULL default ''
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/pv_actor ON /*_*/poll_user_vote (pv_actor);
CREATE INDEX /*i*/pv_poll_id ON /*_*/poll_user_vote (pv_poll_id);
CREATE INDEX /*i*/pv_pc_id ON /*_*/poll_user_vote (pv_pc_id);
