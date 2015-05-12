CREATE OR REPLACE FUNCTION public.get_user_profile(
    i_user_id integer,
    i_my_user_id integer DEFAULT NULL
)
RETURNS public.t_user_profile AS
$BODY$
DECLARE
    r_user_profile public.t_user_profile;
    g_my_geo geography;
BEGIN

    SELECT geography INTO g_my_geo
        FROM public.users_index
        WHERE user_id = i_my_user_id;

    SELECT  u.id AS user_id,
            vk.vk_id,
            u.name,
            u.sex,
            extract('year' from age(u.bdate)) AS age,
            u.about,
            c.created_at AS last_activity,
            ST_Distance(ui.geography, g_my_geo)::integer,
            0 AS weight,
            0 AS is_deleted

        INTO r_user_profile

        FROM public.users AS u
        INNER JOIN public.users_info_vk AS vk
            ON vk.user_id = u.id
        LEFT JOIN public.checkins AS c
            ON c.user_id = u.id
        INNER JOIN public.users_index AS ui
            ON ui.user_id = u.id
        WHERE u.id = i_user_id;

    RETURN r_user_profile;

END
$BODY$
    LANGUAGE plpgsql STABLE;


