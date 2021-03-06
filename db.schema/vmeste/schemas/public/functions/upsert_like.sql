CREATE OR REPLACE FUNCTION public.upsert_like(
    i_from_user_id integer,
    i_to_user_id integer,
    i_is_liked integer
)
RETURNS integer AS
$BODY$
DECLARE

BEGIN

    BEGIN

        INSERT INTO public.likes
            (user1_id, user2_id, liked_at, is_liked, is_send, is_blocked)
            VALUES (
                i_from_user_id,
                i_to_user_id,
                now(),
                i_is_liked::boolean,
                'f',
                'f'
            );

        RETURN 1;

    EXCEPTION WHEN unique_violation THEN

        UPDATE public.likes
            SET liked_at = now()
            WHERE   user1_id = i_from_user_id AND
                    user2_id = i_to_user_id;

    END;

    RETURN 0;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


