----------------------------------------------------------------------------
--

CREATE OR REPLACE FUNCTION public.upsert_user_by_vk_id(
    s_vk_id varchar,
    i_sex integer,
    s_name varchar,
    d_bdate date,
    s_about varchar,
    s_avatar_url varchar
)
    RETURNS record AS
$BODY$
DECLARE
    r_user record;
    i_id integer;
BEGIN

    SELECT user_id, 0 AS is_new  INTO r_user
        FROM public.users_info_vk
        WHERE vk_id = s_vk_id -- uniq
        LIMIT 1;

    IF FOUND THEN

        UPDATE public.users
            SET about = s_about,
                name = s_name,
                sex = i_sex
            WHERE id = r_user.user_id;

    ELSE

        i_id := nextval('users_id_seq'::regclass);

        PERFORM public.add_user(
            i_id,
            i_sex,
            s_name,
            d_bdate,
            s_about,
            s_vk_id,
            s_avatar_url
        );

        r_user.user_id := i_id;
        r_user.is_new := 1;

    END IF;

    RETURN r_user;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;

