CREATE FOREIGN TABLE public.users_index (
    user_id integer NOT NULL,
    geography geography NULL,
    sex integer NOT NULL,
    friends_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    groups_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    likes_count integer NOT NULL DEFAULT 0,
    novelty_weight integer NOT NULL DEFAULT 0,
    age integer NOT NULL,
    city_id integer NULL,
    region_id integer NULL
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

