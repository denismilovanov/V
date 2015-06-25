-------------------------------------------------------------
-- список друзей ВК

CREATE TABLE users_friends_vk (
    user_id integer NOT NULL REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    friend_id integer NOT NULL,
    CONSTRAINT users_friends_vk_pkey PRIMARY KEY (user_id, friend_id)
);

