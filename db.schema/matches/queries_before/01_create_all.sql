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

ALTER FOREIGN TABLE public.users_index
    ADD COLUMN friendliness numeric(3, 2) NOT NULL DEFAULT 0.50,
    ADD COLUMN popularity numeric(3, 2) NOT NULL DEFAULT 0.50;

ALTER FOREIGN TABLE public.users_index
    ADD COLUMN is_show_male boolean NOT NULL DEFAULT FALSE,
    ADD COLUMN is_show_female boolean NOT NULL DEFAULT FALSE;

ALTER TABLE public.users_index
    ADD COLUMN audio_vk_ids integer[] NOT NULL DEFAULT array[]::integer[];

ALTER TABLE public.users_index
    ADD COLUMN universities_vk_ids integer[] NOT NULL DEFAULT array[]::integer[];

ALTER FOREIGN TABLE public.users_index
    ADD COLUMN activities_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN interests_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN books_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN games_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN movies_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN music_vk_ids integer[] NOT NULL DEFAULT array[]::integer[];
