CREATE OR REPLACE FUNCTION public.add_push (
    i_push_type_id integer,
    i_to_user_id integer,
    i_from_user_id integer
)
RETURNS bigint AS
$BODY$
DECLARE
    i_id bigint;
BEGIN

    INSERT INTO push_queue
        (from_user_id, to_user_id, push_type_id)
        VALUES (
            i_from_user_id,
            i_to_user_id,
            i_push_type_id
        )
        RETURNING id INTO i_id;

    RETURN i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


