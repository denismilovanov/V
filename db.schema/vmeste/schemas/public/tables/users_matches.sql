-------------------------------------------------------------
-- построенные совпадения

CREATE TABLE public.users_matches (
    user_id integer NOT NULL REFERENCES public.users (id),
    match_user_id integer NOT NULL REFERENCES public.users (id),
    distance integer NOT NULL,
    weight numeric NULL,
    CONSTRAINT users_matches_pkey PRIMARY KEY (user_id, match_user_id)
);

CREATE INDEX users_matches_fetch_idx
    ON public.users_matches
    USING btree(user_id, weight DESC NULLS LAST);


