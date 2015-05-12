-------------------------------------------------------------
-- список групп пользователей ВК

CREATE TABLE users_groups_vk (
    user_id integer NOT NULL REFERENCES users (id),
    group_id integer NOT NULL REFERENCES groups_vk (id),
    CONSTRAINT users_groups_vk_pkey PRIMARY KEY (user_id, group_id)
);

