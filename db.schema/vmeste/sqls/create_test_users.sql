BEGIN;

SELECT public.add_user(
    id,
    1,
    NULL,
    ('1985-01-01'::date + interval '1 year' * (random() * 10)::integer)::date,
    ('Тестовый #' || id::varchar)::varchar,
    (-id)::varchar,
    ''
)
    FROM generate_series(170001, 199999) AS id; -- 199999

SELECT public.add_user(
    id,
    2,
    NULL,
    ('1985-01-01'::date + interval '1 year' * (random() * 10)::integer)::date,
    ('Тестовый #' || id::varchar)::varchar,
    (-id)::varchar,
    ''
)
    FROM generate_series(270001, 299999) AS id; -- 299999

ALTER SEQUENCE users_id_seq RESTART WITH 300000;

COMMIT;

INSERT INTO users_index
    (user_id, sex, age)
    SELECT id, coalesce(sex, 0), coalesce(extract('years' from age(bdate)), 20)
        FROM users
        WHERE NOT EXISTS(
            SELECT 1
                FROM users_index
                WHERE user_id = id
        );

UPDATE users_index AS i
    SET groups_vk_ids = coalesce((SELECT array_agg(g.group_id)
                                FROM users_groups_vk AS g WHERE g.user_id = i.user_id), array[]::integer[]);

UPDATE users_index AS i
    SET friends_vk_ids = coalesce((SELECT array_agg(g.friend_id)
                                FROM users_friends_vk AS g WHERE g.user_id = i.user_id), array[]::integer[]);

update users_index  set
    groups_vk_ids = array[44233743,56929213,(random() * 100000)::integer]
    where user_id between 100000 and 299999;

update users_index  set
    friends_vk_ids = array[(random() * 1000)::integer, (random() * 1000)::integer,
    (random() * 1000)::integer, (random() * 1000)::integer, case when random() < 0.1 then 2404643 else (random() * 1000)::integer end]
    where user_id between 100000 and 299999;





SELECT public.checkin(
    id,
    59.7 + 0.4 * random(),
    30.1 + 0.4 * random()
)
    FROM users
    WHERE id BETWEEN 100000 and 299999;






