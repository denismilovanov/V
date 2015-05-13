-------------------------------------------------------------
-- поисковый индекс

CREATE TABLE public.users_index (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users (id),
    geography geography NULL,
    sex integer NOT NULL,
    friends_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    groups_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    likes_count integer NOT NULL DEFAULT 0,
    novelty_weight integer NOT NULL DEFAULT 0,
    age integer NOT NULL
);

CREATE INDEX users_index_1
    ON users_index
    USING gist (geography);

CREATE INDEX users_index_5
    ON users_index
    USING gist (geography, age)
    WHERE sex IN (1);

CREATE INDEX users_index_6
    ON users_index
    USING gist (geometry);

CREATE INDEX users_index_7
    ON users_index
    USING gist (user_id, geometry);

ALTER TABLE public.users_index
    ADD COLUMN geometry geometry(Point, 4326) NULL;
