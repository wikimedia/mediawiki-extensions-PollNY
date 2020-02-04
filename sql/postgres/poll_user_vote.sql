DROP SEQUENCE IF EXISTS poll_user_vote_pv_id_seq CASCADE;
CREATE SEQUENCE poll_user_vote_pv_id_seq;

CREATE TABLE poll_user_vote (
  pv_id INTEGER NOT NULL PRIMARY KEY DEFAULT nextval('poll_user_vote_pv_id_seq'),
  pv_poll_id INTEGER NOT NULL default 0,
  pv_pc_id SMALLINT default 0,
  pv_actor INTEGER NOT NULL,
  pv_date TIMESTAMPTZ default NULL
);

ALTER SEQUENCE poll_user_vote_pv_id_seq OWNED BY poll_user_vote.pv_id;

CREATE INDEX pv_actor ON poll_user_vote (pv_actor);
CREATE INDEX pv_poll_id ON poll_user_vote (pv_poll_id);
CREATE INDEX pv_pc_id ON poll_user_vote (pv_pc_id);
