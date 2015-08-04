-------------------------------------------------------------
-- поисковый индекс

CREATE TABLE public.users_index (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    geography geography NULL,
    sex integer NOT NULL,
    friends_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    groups_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    likes_count integer NOT NULL DEFAULT 0,
    novelty_weight integer NOT NULL DEFAULT 0,
    age integer NOT NULL,
    last_activity_at timestamp with time zone NOT NULL DEFAULT now()
);

CREATE INDEX users_index_geography
    ON users_index
    USING gist (geography);

CREATE INDEX users_fetch_sex1
    ON users_index
    USING btree(user_id, age)
    WHERE sex = 1;

CREATE INDEX users_fetch_sex2
    ON users_index
    USING btree(user_id, age)
    WHERE sex = 2;

ALTER TABLE public.users_index
    ADD COLUMN city_id integer NULL,
    ADD COLUMN region_id integer NULL;

ALTER TABLE public.users_index
    ADD COLUMN friendliness numeric(3, 2) NOT NULL DEFAULT 0.50,
    ADD COLUMN popularity numeric(3, 2) NOT NULL DEFAULT 0.50;

ALTER TABLE public.users_index
    ADD COLUMN last_updated_at timestamp with time zone NOT NULL DEFAULT now();

CREATE INDEX users_fetch_all
    ON users_index
    USING btree(user_id, region_id, age, sex);

--
ALTER TABLE public.users_index
    ADD COLUMN is_show_male boolean NOT NULL DEFAULT FALSE,
    ADD COLUMN is_show_female boolean NOT NULL DEFAULT FALSE;

--
ALTER TABLE public.users_index
    ADD COLUMN audio_vk_ids integer[] NOT NULL DEFAULT array[]::integer[];

ALTER TABLE public.users_index
    ADD COLUMN universities_vk_ids integer[] NOT NULL DEFAULT array[]::integer[];

ALTER TABLE public.users_index
    ADD COLUMN activities_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN interests_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN books_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN games_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN movies_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    ADD COLUMN music_vk_ids integer[] NOT NULL DEFAULT array[]::integer[];



