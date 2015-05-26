CREATE OR REPLACE FUNCTION public.get_geography(
    d_longitude double precision,
    d_latitide double precision
)
RETURNS public.t_geography AS
$BODY$
DECLARE
    r_city record;
    r_region record;
    g_geo geometry;
    r_result public.t_geography;
BEGIN

    g_geo := ST_GeometryFromText('POINT(' || d_longitude::varchar || ' ' || d_latitide::varchar || ')', 4326);

    SELECT * INTO r_city
        FROM planet_osm_polygon
        WHERE   ST_Within(g_geo, way) AND
                place IN ('city', 'town')
        ORDER BY name
        LIMIT 1;

    r_city.way := NULL;

    RAISE NOTICE 'city = %', r_city;

    IF FOUND THEN

        WITH way AS (
            SELECT way
                FROM planet_osm_polygon
                WHERE osm_id = r_city.osm_id
                LIMIT 1
        )
        SELECT * INTO r_region
            FROM planet_osm_polygon
            WHERE   way && (SELECT way FROM way) AND
                    admin_level = '4'
            ORDER BY name
            LIMIT 1;

        r_region.way := NULL;

        RAISE NOTICE 'region = %', r_region;

    ELSE

        SELECT * INTO r_region
            FROM planet_osm_polygon
            WHERE   ST_Within(g_geo, way) AND
                    admin_level = '4'
            ORDER BY name
            LIMIT 1;

        r_region.way := NULL;

        RAISE NOTICE 'region = %', r_region;

    END IF;

    r_result.city_id := r_city.osm_id;
    r_result.city_name := r_city.name;
    r_result.region_id := r_region.osm_id;
    r_result.region_name := r_region.name;

    RETURN r_result;

END
$BODY$
    LANGUAGE plpgsql IMMUTABLE;


