CREATE OR REPLACE FUNCTION public.checkin(
    i_user_id integer,
    dp_latitude double precision,
    dp_longitude double precision
)
RETURNS void AS
$BODY$
DECLARE
    g_geo geometry;
    g_geography geography;
BEGIN

    g_geo := ST_GeomFromText('POINT(' || dp_longitude || ' ' || dp_latitude || ')');
    g_geography := ST_GeogFromText('POINT(' || dp_longitude || ' ' || dp_latitude || ')');

    -- обновляем чекин
    UPDATE checkins
        SET latitude = dp_latitude,
            longitude = dp_longitude,
            geo = g_geo,
            created_at = now()
        WHERE user_id = i_user_id;

    IF NOT FOUND THEN

        INSERT INTO checkins
            SELECT  i_user_id,
                    dp_latitude,
                    dp_longitude,
                    now(),
                    g_geo;

    END IF;

    -- обновляем поисковый индекс
    UPDATE users_index
        SET geography = g_geography
        WHERE user_id = i_user_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


