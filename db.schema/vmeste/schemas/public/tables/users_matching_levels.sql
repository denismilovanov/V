-------------------------------------------------------------
--

CREATE TABLE public.users_matching_levels (
    user_id integer NOT NULL REFERENCES public.users (id),
    level_id integer NOT NULL,
    users_ids integer[] NOT NULL DEFAULT array[]::integer[],
    CONSTRAINT users_matching_levels_pkey PRIMARY KEY (user_id, level_id)
);




