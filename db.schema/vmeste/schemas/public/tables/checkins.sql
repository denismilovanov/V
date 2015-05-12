-------------------------------------------------------------
-- чекины

CREATE TABLE public.checkins (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users (id),
    latitude double precision,
    longitude double precision,
    created_at timestamp without time zone,
    geo geometry(Point)
);

CREATE INDEX checkins_gix
    ON public.checkins
    USING gist(geo);

ALTER TABLE public.checkins
    ALTER COLUMN created_at TYPE timestamp with time zone;
