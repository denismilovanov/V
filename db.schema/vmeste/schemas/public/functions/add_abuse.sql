CREATE OR REPLACE FUNCTION public.add_abuse(
    i_from_user_id integer,
    i_to_user_id integer,
    t_text text
)
RETURNS integer AS
$BODY$
DECLARE
    i_id integer;
BEGIN

    INSERT INTO public.abuses
        (from_user_id, to_user_id, message)
        VALUES (
            i_from_user_id,
            i_to_user_id,
            t_text
        )
        RETURNING id INTO i_id;

    RETURN i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


