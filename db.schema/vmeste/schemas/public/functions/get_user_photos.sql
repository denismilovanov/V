CREATE OR REPLACE FUNCTION public.get_user_photos(
    i_user_id integer
)
RETURNS SETOF public.users_photos AS
$BODY$
DECLARE

BEGIN

    RETURN QUERY
        SELECT *
            FROM public.users_photos
            WHERE user_id = i_user_id
            ORDER BY rank, id;
END
$BODY$
    LANGUAGE plpgsql STABLE;


