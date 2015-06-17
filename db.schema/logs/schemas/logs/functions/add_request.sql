CREATE OR REPLACE FUNCTION logs.add_request(
    j_request json
)
RETURNS bigint AS
$BODY$
DECLARE
    i_request_id bigint;
BEGIN

    INSERT INTO logs.requests
        (request)
        VALUES (
            j_request
        )
        RETURNING id INTO i_request_id;

    RETURN i_request_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


