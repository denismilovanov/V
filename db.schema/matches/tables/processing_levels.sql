-------------------------------------------------------------
--

CREATE TABLE public.processing_levels (
    level_id integer NOT NULL PRIMARY KEY,
    users_ids integer[] NOT NULL DEFAULT array[]::integer[]
);
