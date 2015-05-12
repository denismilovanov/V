
CREATE OR REPLACE FUNCTION public.search_around_part1(
    i_user_id integer,
    i_radius integer,
    ai_sex integer[],
    i_age_from integer,
    i_age_to integer,
    dp_my_latitude double precision,
    dp_my_longitude double precision
)
RETURNS
    TABLE(
        user_id integer,
        age integer,
        sex smallint,
        bdate date,
        distance double precision,
        last_activity double precision,
        vk_id character varying,
        name character varying,
        about character varying,
        avatar_url character varying
    )
LANGUAGE plpgsql
AS $function$
DECLARE
    g_my_geo geography := geography(ST_MakePoint(dp_my_latitude, dp_my_latitude));
BEGIN

    SELECT user_id
      FROM users_index
      WHERE age BETWEEN i_age_from AND i_age_to AND
            ST_DWithin(geography, g_my_geo, i_radius * 1000) AND
            sex = ANY(ai_sex);


END;
$function$

