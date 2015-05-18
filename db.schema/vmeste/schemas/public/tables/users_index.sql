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
    age integer NOT NULL,
    last_activity_at timestamp with time zone NOT NULL DEFAULT now()
);

CREATE INDEX users_index_geography
    ON users_index
    USING gist (geography);

