
CREATE OR REPLACE FUNCTION public.search_around(integer)
RETURNS TABLE(
        user_id integer,
        age integer,
        sex smallint,
        bdate date,
        distance double precision,
        last_activity double precision,
        vk_id character varying,
        name character varying,
        about character varying,
        avatar_url character varying
)
LANGUAGE plpgsql
AS $function$
DECLARE
    my_user RECORD;
    other_user RECORD;
    dist_deltas RECORD;
    my_user_id ALIAS FOR $1;
    fetched_cnt int;
    fetched_users int[];
BEGIN
    SELECT 0 INTO fetched_cnt;
    SELECT ARRAY[0] INTO fetched_users;

    -----------------------------------------------------------------------------------------
    --

    SELECT
        users.id, checkins.geo,
        --          ST_LineToCurve(ST_Buffer(checkins.geo, 3)) as geo_curve,  -- ST_LineToCurve( .. ) -- увеличиваем гео на 3 градуса
        ST_Distance(checkins.geo, ST_Translate(checkins.geo, 0.1, 0.1), true) as distance, -- Расстояние в км между 0.1 градуса на этой широте-долготе
        CASE WHEN users_settings.radius = 200
           THEN 200000
           ELSE users_settings.radius * 1000
        END as max_distance,

        CASE WHEN users_settings.age_to >= users_settings.age_from AND users_settings.age_to >= 18
           THEN (NOW() - (users_settings.age_to || ' YEARS 364 DAYS')::INTERVAL)::date
           ELSE (NOW() - '150 YEARS'::INTERVAL)::date
        END as min_bdate,

        CASE WHEN users_settings.age_from >= 18
           THEN  (NOW() - (users_settings.age_from || ' YEARS')::INTERVAL)::date
           ELSE  (NOW() - '18 YEARS'::INTERVAL)::date
        END as max_bdate,

        users_settings.is_show_male, users_settings.is_show_female,
        users_settings.age_from, (users_settings.age_from || ' YEARS')::INTERVAL
    FROM users
    INNER JOIN checkins
      ON checkins.user_id = users.id
    INNER JOIN users_settings
      ON users_settings.user_id = users.id
    WHERE users.id = my_user_id
    LIMIT 1
    INTO my_user;

    -----------------------------------------------------------------------------------------
    --

    FOR dist_deltas IN
         SELECT *
         FROM (
              SELECT
                   generate_series(0, 1000, 50) as delta_dist,
    --               ST_Buffer(my_user.geo, 0.1 * generate_series(1, 100, 1)) as geo_curve,
    --               ST_Buffer(my_user.geo, 0.1 * generate_series(1, 100, 1) - 0.1) as geo_curve2

    --               ST_LineToCurve(ST_Buffer(my_user.geo, 0.1 * generate_series(1, 100, 1))) as geo_curve,
    --               ST_LineToCurve(ST_Buffer(my_user.geo, 0.1 * generate_series(1, 100, 1) - 0.1)) as geo_curve2

                   ST_Expand(my_user.geo, 2 + 0.1 * generate_series(5, 100, 5) ) as geo_curve, -- внешная часть бублика
                   ST_Expand(my_user.geo, 1 + 0.1 * generate_series(0, 100, 5) ) as geo_curve2 -- внутренняя часть бублика
              LIMIT
                   CASE
                        WHEN my_user.max_distance < 200000
                        THEN 1
                        ELSE 100
                   END
         ) as tbl
    LOOP
         FOR other_user IN
              SELECT
                   users.id, users.sex, users.bdate, date_part('year', age(users.bdate))::INT as age,
                   ST_Distance(checkins.geo, my_user.geo, true) as distance,
                   extract(epoch from date_trunc('second', checkins.created_at)) as last_activity,

                   users.about,
                   users_info_vk.name, users_info_vk.avatar_url,
                   users_info_vk.vk_id,
                   checkins.geo,
                   1
              FROM checkins
              INNER JOIN users
                   ON users.id = checkins.user_id
              LEFT JOIN likes
                   ON likes.user2_id = checkins.user_id AND likes.user1_id = my_user.id
    --          INNER JOIN likes as me_liked
    --               ON me_liked.user2_id = my_user.id AND me_liked.user1_id = checkins.user_id

              LEFT JOIN users_info_vk
                   ON users_info_vk.user_id = checkins.user_id
              WHERE
                   1 = 1
                   AND checkins.user_id != my_user.id

                   AND checkins.geo && dist_deltas.geo_curve

                   AND (
                        (
                             (dist_deltas.delta_dist > 0) AND NOT (checkins.geo && dist_deltas.geo_curve2)
                        )
                        OR ( true )
                   )

                   AND ST_Distance(checkins.geo, my_user.geo, true) <= (my_user.max_distance + (dist_deltas.delta_dist * 1000))

                   AND users.bdate >= my_user.min_bdate
                   AND users.bdate <= my_user.max_bdate

                   AND CASE
                        WHEN my_user.is_show_male AND my_user.is_show_female AND users.sex > 0
                        THEN TRUE
                        WHEN my_user.is_show_male AND users.sex = 2
                        THEN TRUE
                        WHEN my_user.is_show_female AND users.sex = 1
                        THEN TRUE
                        ELSE FALSE
                   END
                   AND likes.user1_id IS NULL
                   AND users_info_vk.vk_id IS NOT NULL
                   AND NOT (users.id = ANY (fetched_users) )
              ORDER BY last_activity DESC, distance
         LOOP
              fetched_cnt = fetched_cnt + 1;
              fetched_users = fetched_users || array[other_user.id];

              RETURN QUERY VALUES (other_user.id, other_user.age, other_user.sex, other_user.bdate, other_user.distance, other_user.last_activity, other_user.vk_id,
                        other_user.name,
                        other_user.about,
                        other_user.avatar_url
                        --dist_deltas.delta_dist::character varying(255)

                   -- ST_AsText(my_user.geo)::character varying(255),
                   -- ST_AsText(other_user.geo)::character varying(255),
                   -- ST_AsText(dist_deltas.geo_curve)::character varying(255)
              );

    --          RETURN;
              IF fetched_cnt >= 15 THEN
                   EXIT;
              END IF;
         END LOOP;

         IF fetched_cnt >= 15 THEN
              EXIT;
         END IF;
    END LOOP;


/*
     RETURN QUERY VALUES (fetched_cnt, my_user.max_distance, 0::smallint, NOW()::date, my_user.distance::double precision, 0::double precision,
          'vk_id'::character varying(45),
          'name'::character varying(255),
          ST_asText(my_user.geo)::character varying(2550),
          fetched_users::character varying(255)
          );
*/
     RETURN;
END;
$function$

