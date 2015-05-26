CREATE OR REPLACE FUNCTION public.checkin(
    i_user_id integer,
    dp_latitude double precision,
    dp_longitude double precision,
    i_city_id integer,
    i_region_id integer
)
RETURNS void AS
$BODY$
DECLARE
    g_geography geography;
BEGIN

    g_geography := ST_GeogFromText('POINT(' || dp_longitude || ' ' || dp_latitude || ')');

    -- добавляем чекин
    INSERT INTO public.checkins
        (user_id, latitude, longitude, geography)
        SELECT  i_user_id,
                dp_latitude,
                dp_longitude,
                g_geography;

    -- обновляем поисковый индекс
    UPDATE public.users_index
        SET geography = g_geography,
            last_activity_at = now(),
            city_id = i_city_id,
            region_id = i_region_id
        WHERE user_id = i_user_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


