DROP SEQUENCE IF EXISTS poll_choice_pc_id_seq CASCADE;
CREATE SEQUENCE poll_choice_pc_id_seq;

CREATE TABLE poll_choice (
  pc_id INTEGER NOT NULL PRIMARY KEY DEFAULT nextval('poll_choice_pc_id_seq'),
  pc_poll_id INTEGER NOT NULL default 0,
  pc_order INTEGER default 0,
  pc_text TEXT NOT NULL,
  pc_vote_count INTEGER NOT NULL default 0
);

ALTER SEQUENCE poll_choice_pc_id_seq OWNED BY poll_choice.pc_id;

CREATE INDEX pc_poll_id ON poll_choice (pc_poll_id);
