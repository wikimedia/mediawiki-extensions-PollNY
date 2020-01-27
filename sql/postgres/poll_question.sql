DROP SEQUENCE IF EXISTS poll_question_poll_id_seq CASCADE;
CREATE SEQUENCE poll_question_poll_id_seq;

CREATE TABLE poll_question (
  poll_id INTEGER NOT NULL PRIMARY KEY DEFAULT nextval('poll_question_poll_id_seq'),
  poll_page_id INTEGER NOT NULL default 0,
  poll_actor INTEGER NOT NULL,
  poll_text TEXT NOT NULL,
  poll_image TEXT NOT NULL default '',
  poll_status SMALLINT default 1,
  poll_vote_count SMALLINT default 0,
  poll_question_vote_count SMALLINT default 0,
  poll_date TIMESTAMPTZ default NULL,
  poll_random DOUBLE PRECISION default 0
);

ALTER SEQUENCE poll_question_poll_id_seq OWNED BY poll_question.poll_id;

CREATE INDEX poll_actor ON poll_question (poll_actor);
CREATE INDEX poll_random ON poll_question (poll_random);
