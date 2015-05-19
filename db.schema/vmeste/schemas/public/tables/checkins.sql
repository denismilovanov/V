-------------------------------------------------------------
-- все чекины - последовательный лог

CREATE TABLE public.checkins (
    id bigserial NOT NULL PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users (id),
    latitude double precision NOT NULL,
    longitude double precision NOT NULL,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    geography geography NOT NULL
);

