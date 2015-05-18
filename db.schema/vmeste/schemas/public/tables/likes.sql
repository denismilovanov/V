-------------------------------------------------------------
-- лайки

CREATE TABLE likes (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('likes_id_seq'::regclass),
    user1_id integer NOT NULL,
    user2_id integer NOT NULL,
    liked_at timestamp with time zone DEFAULT now(),
    is_liked boolean,
    is_send boolean,
    is_blocked boolean,
    reason smallint,
    is_new boolean DEFAULT true
);

CREATE INDEX likes_liked_blocked_send
    ON likes
    USING btree (is_liked, is_blocked, is_send);

CREATE UNIQUE INDEX likes_unique_users
    ON likes
    USING btree(user1_id, user2_id);
