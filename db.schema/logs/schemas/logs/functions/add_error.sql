CREATE OR REPLACE FUNCTION logs.add_error(
    s_type_index varchar,
    t_header text,
    t_message text
)
RETURNS void AS
$BODY$
DECLARE
    i_type_id integer;
BEGIN

    SELECT id INTO i_type_id FROM logs.errors_types WHERE index = s_type_index;

    INSERT INTO logs.errors
        (header, message, type_id)
        VALUES (
            t_header,
            t_message,
            i_type_id
        );

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


