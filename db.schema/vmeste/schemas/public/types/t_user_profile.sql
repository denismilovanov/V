CREATE TYPE public.t_user_profile AS (
    user_id integer,
    vk_id integer,
    name varchar,
    sex integer,
    age integer,
    about varchar,
    last_activity timestamp with time zone,
    distance integer,
    weight numeric,
    is_deleted integer
);
