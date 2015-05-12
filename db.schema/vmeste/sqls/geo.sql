SELECT  ST_GeomFromText('POINT(30.6 60.0)') &&
        ST_Expand(ST_GeomFromText('POINT(30.4 60.0)'), 5000);

SELECT max(ST_Distance(ST_GeogFromText('POINT(30.4 60.0)'), geo, true))
    FROM checkins
    WHERE ST_Expand(ST_GeogFromText('POINT(30.4 60.0)'), 0.01) ~ geo;

SELECT ST_GeogFromText('POINT(30.6 60.0)');

SELECT st_distance(geog, st_setsrid(st_makepoint(30.4, 60),4326))
FROM checkins
WHERE geog ~ st_setsrid(st_makepoint(30.4, 60),4326)
ORDER BY geog <->
LIMIT 10;

explain analyze SELECT *, st_distance(geog, st_setsrid(st_makepoint(30.4, 60),4326), true)
FROM checkins
WHERE geog <-> st_setsrid(st_makepoint(30.4, 60),4326) > 0
ORDER BY st_distance(geog, st_setsrid(st_makepoint(30.4, 60),4326), true) DESC
LIMIT 10;

SELECT *, st_distance(geog, st_setsrid(st_makepoint(30.4, 60),4326), true)
FROM checkins
WHERE ST_DWithin(geog, st_setsrid(st_makepoint(30.4, 60),4326), 10000)
ORDER BY st_distance(geog, st_setsrid(st_makepoint(30.4, 60),4326), true) DESC
LIMIT 10;


SELECT *, st_distance(geo,st_makepoint(30.4, 60), true)
FROM checkins
WHERE geo <-> st_makepoint(30.4, 60) > 0
ORDER BY st_distance(geo, st_makepoint(30.4, 60), true) DESC
LIMIT 10;

explain analyze
SELECT count(*) -- *, st_distance(geography, geography(ST_MakePoint(30.3, 59.9)))
FROM users_index
WHERE
 age between 21 and 21 and
 ST_DWithin(geography, geography(ST_MakePoint(30.3, 59.9)), 10000)
;
--ORDER BY user_id
--LIMIT 10;

select st_setsrid(st_makepoint(30.6, 60),4326) <-> st_setsrid(st_makepoint(30.4, 60),4326);
