CREATE OR REPLACE FUNCTION public.format_date(
    ts_ts timestamp with time zone,
    i_time_zone integer
)
RETURNS varchar AS
$BODY$
DECLARE

BEGIN

    i_time_zone := i_time_zone * -1;

    RETURN date_trunc('second', ts_ts at time zone i_time_zone::varchar);

END
$BODY$
    LANGUAGE plpgsql IMMUTABLE;


