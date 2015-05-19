CREATE OR REPLACE FUNCTION public.add_test_message(
    i_me_id integer,
    i_buddy_id integer,
    t_text varchar,
    b_i boolean
)
RETURNS void AS
$BODY$
DECLARE
    i_id bigint;
BEGIN

    i_id := public.add_message(i_me_id, i_buddy_id, t_text, b_i);

    RAISE NOTICE '%', i_id;

    i_id := public.add_message(i_buddy_id, i_me_id, t_text, NOT b_i);

    RAISE NOTICE '%', i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


