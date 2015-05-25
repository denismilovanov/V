CREATE SERVER main_server FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host 'localhost', dbname 'vmeste', port '5432');
CREATE USER MAPPING FOR vmeste SERVER main_server OPTIONS (user 'vmeste', password 'vmeste');

CREATE FOREIGN TABLE public.users_index (
    user_id integer NOT NULL,
    geography geography NULL,
    sex integer NOT NULL,
    friends_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    groups_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    likes_count integer NOT NULL DEFAULT 0,
    novelty_weight integer NOT NULL DEFAULT 0,
    age integer NOT NULL
)
SERVER main_server;

CREATE TABLE IF NOT EXISTS public.matching_levels (
    level_id integer NOT NULL PRIMARY KEY,
    users_ids integer[] NOT NULL DEFAULT array[]::integer[]
);

CREATE TABLE IF NOT EXISTS public.processing_levels (
    level_id integer NOT NULL PRIMARY KEY,
    users_ids integer[] NOT NULL DEFAULT array[]::integer[]
);
