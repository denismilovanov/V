-------------------------------------------------------------
-- все чекины - последовательный лог

CREATE TABLE public.checkins (
    id bigserial NOT NULL PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    latitude double precision NOT NULL,
    longitude double precision NOT NULL,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    geography geography NOT NULL
);

CREATE INDEX checkins_user_id_idx
    ON public.checkins
    USING btree(user_id);
