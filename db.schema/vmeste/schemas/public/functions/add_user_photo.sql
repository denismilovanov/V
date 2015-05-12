CREATE OR REPLACE FUNCTION public.add_user_photo(
    i_user_id integer,
    i_source_id integer,
    s_url varchar,
    s_hash varchar,
    s_extension varchar
)
RETURNS integer AS
$BODY$
DECLARE
    i_id integer;
BEGIN

    INSERT INTO public.users_photos
        (user_id, source_id, url, hash, extension, rank)
        VALUES (
            i_user_id,
            i_source_id,
            s_url,
            s_hash,
            s_extension,
            COALESCE((SELECT max(rank) + 1 FROM public.users_photos WHERE user_id = i_user_id), 0)
        )
        RETURNING id INTO i_id;

    RETURN i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


