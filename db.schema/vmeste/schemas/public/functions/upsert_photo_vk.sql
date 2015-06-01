-------------------------------------------------------------
--

CREATE OR REPLACE FUNCTION public.upsert_photo_vk(
    i_user_id integer,
    s_photo_url varchar,
    i_rank integer
)
RETURNS bigint AS
$BODY$
DECLARE
    i_photo_id integer;
BEGIN

    UPDATE public.users_photos
        SET rank = i_rank
        WHERE   user_id = user_id AND
                lower(url) = lower(s_photo_url)
        RETURNING id INTO i_photo_id;

    IF NOT FOUND THEN

        INSERT INTO public.users_photos
            (user_id, source_id, url, rank)
            VALUES (
                i_user_id,
                1,
                s_photo_url,
                i_rank
            )
            RETURNING id INTO i_photo_id;

    END IF;

    RETURN i_photo_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


