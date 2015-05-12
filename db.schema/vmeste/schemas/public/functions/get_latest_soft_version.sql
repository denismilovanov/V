----------------------------------------------------------------------------
--

CREATE OR REPLACE FUNCTION public.get_latest_soft_version(
    i_device_type integer
)
    RETURNS record AS
$BODY$
DECLARE
    r_result record;
BEGIN

    SELECT  id AS version,
            description
        INTO r_result
        FROM public.soft_versions
        WHERE   device_type = i_device_type AND
                is_actual
        ORDER BY created_at DESC
        LIMIT 1;

    RETURN r_result;

END
$BODY$
    LANGUAGE plpgsql STABLE;

