----------------------------------------------------------------------------
--

CREATE OR REPLACE FUNCTION public.get_matching_users_ids(
    i_user_id integer,
    i_limit integer
)
    RETURNS SETOF record AS
$BODY$
DECLARE
    r_record record;
    i_count integer := 0;
BEGIN

    FOR r_record IN EXECUTE 'SELECT level_id, unnest(users_ids) AS user_id
                                            FROM public.matching_levels_fresh_' || i_user_id || '
                                            ORDER BY level_id DESC'
    LOOP
        i_count := i_count + 1;
        RETURN NEXT r_record;
        IF i_count >= i_limit THEN
            RETURN;
        END IF;
    END LOOP;


    FOR r_record IN EXECUTE 'SELECT level_id, unnest(users_ids) AS user_id
                                            FROM public.matching_levels_' || i_user_id || '
                                            ORDER BY level_id DESC'
    LOOP
        i_count := i_count + 1;
        RETURN NEXT r_record;
        IF i_count >= i_limit THEN
            RETURN;
        END IF;
    END LOOP;

END
$BODY$
    LANGUAGE plpgsql STABLE;

