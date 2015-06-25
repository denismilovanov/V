-------------------------------------------------------------
--

CREATE TABLE public.users_matches (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    last_reindexed_at timestamp without time zone NOT NULL DEFAULT now()
);

CREATE INDEX users_matches_last_reindexed_at_idx
    ON public.users_matches
    USING btree(last_reindexed_at);
