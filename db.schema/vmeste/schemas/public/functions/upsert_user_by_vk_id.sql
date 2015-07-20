----------------------------------------------------------------------------
--

CREATE OR REPLACE FUNCTION public.upsert_user_by_vk_id(
    s_vk_id varchar,
    i_sex integer,
    s_name varchar,
    d_bdate date,
    s_about varchar,
    s_avatar_url varchar,
    i_time_zone integer
)
    RETURNS record AS
$BODY$
DECLARE
    r_user record;
    i_id integer;
    d_current_bdate date;
BEGIN

    SELECT id AS user_id, 0 AS is_new INTO r_user
        FROM public.users
        WHERE vk_id = s_vk_id::integer -- uniq
        LIMIT 1;

    IF FOUND THEN

        UPDATE public.users
            SET name = s_name,
                sex = i_sex,
                time_zone = i_time_zone,
                is_deleted = 'f'
            WHERE id = r_user.user_id
            RETURNING bdate INTO d_current_bdate;

        IF d_current_bdate IS NULL AND d_bdate IS NOT NULL THEN
            UPDATE public.users
                SET bdate = d_bdate
                WHERE id = r_user.user_id;
        END IF;

    ELSE

        i_id := nextval('users_id_seq'::regclass);

        PERFORM public.add_user(
            i_id,
            i_sex,
            s_name,
            d_bdate,
            s_about,
            s_vk_id,
            s_avatar_url,
            i_time_zone
        );

        r_user.user_id := i_id;
        r_user.is_new := 1;

    END IF;

    RETURN r_user;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;

