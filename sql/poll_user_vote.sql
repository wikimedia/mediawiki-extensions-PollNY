CREATE TABLE /*_*/poll_user_vote (
  pv_id int(11) NOT NULL PRIMARY KEY auto_increment,
  pv_poll_id int(11) NOT NULL default 0,
  pv_pc_id int(5) default 0,
  pv_user_id int(11) NOT NULL default 0,
  pv_user_name varchar(255) NOT NULL default '',
  pv_date datetime default NULL
  -- MW standard version for timestamps:
  --`pv_date` binary(14) NOT NULL default ''
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/pv_user_id ON /*_*/poll_user_vote (pv_user_id);
CREATE INDEX /*i*/pv_poll_id ON /*_*/poll_user_vote (pv_poll_id);
CREATE INDEX /*i*/pv_pc_id ON /*_*/poll_user_vote (pv_pc_id);
