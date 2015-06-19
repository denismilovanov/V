CREATE OR REPLACE FUNCTION logs.add_request(
    j_request json,
    i_user_id integer
)
RETURNS bigint AS
$BODY$
DECLARE
    i_request_id bigint;
BEGIN

    INSERT INTO logs.requests
        (request, user_id)
        VALUES (
            j_request, i_user_id
        )
        RETURNING id INTO i_request_id;

    RETURN i_request_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


