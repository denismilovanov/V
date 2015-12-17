-------------------------------------------------------------
-- матчи

CREATE TABLE public.matches (
    user1_id integer NOT NULL REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    user2_id integer NOT NULL REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    matched_at timestamp with time zone DEFAULT now(),
    CONSTRAINT matches_pkey PRIMARY KEY (user1_id, user2_id)
);


