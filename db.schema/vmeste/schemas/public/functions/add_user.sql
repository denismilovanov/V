CREATE OR REPLACE FUNCTION public.add_user(
    i_id integer,
    i_sex integer,
    s_name varchar,
    d_bdate date,
    s_about varchar,
    s_vk_id varchar,
    s_avatar_url varchar,
    i_time_zone integer
)
RETURNS integer AS
$BODY$
DECLARE

BEGIN

    IF s_name IS NULL AND i_sex = 1 THEN
        SELECT name INTO s_name
            FROM unnest(array[
                'Анастасия', 'Юлия', 'Мария', 'Анна', 'Екатерина', 'Виктория', 'Кристина', 'Ольга', 'Ирина', 'Елена',
                'Татьяна', 'Светлана', 'Настя', 'Ксения', 'Дарья', 'Александра', 'Алина', 'Наталья', 'Марина', 'Евгения',
                'Валерия', 'Катя', 'Даша', 'Аня', 'Полина', 'Яна', 'Юля', 'Диана', 'Карина', 'Алёна',
                'Елизавета', 'Маша', 'Маргарита', 'Наташа', 'Катерина', 'Оля'
            ]) AS name
            ORDER BY random()
            LIMIT 1;
    END IF;

    IF s_name IS NULL AND i_sex = 2 THEN
        SELECT name INTO s_name
            FROM unnest(array[
                'Александр', 'Сергей', 'Дмитрий', 'Андрей', 'Алексей', 'Евгений', 'Максим', 'Денис', 'Антон', 'Роман',
                'Илья', 'Иван', 'Никита', 'Игорь', 'Дима', 'Павел', 'Олег', 'Владимир', 'Кирилл', 'Михаил', 'Николай',
                'Артём', 'Руслан', 'Виталий', 'Саша', 'Владислав', 'Вадим', 'Влад', 'Константин', 'Егор'
            ]) AS name
            ORDER BY random()
            LIMIT 1;
    END IF;

    RAISE NOTICE 'public.add_user: %', s_name;

    BEGIN
        INSERT INTO users
            (id, updated_at, sex, name, bdate, about, time_zone, vk_id)
            VALUES (
                i_id, now(), i_sex, s_name, d_bdate, s_about, i_time_zone, s_vk_id::integer
            );
    EXCEPTION WHEN unique_violation THEN
        -- есть ситуации, когда уникальность vk_id нарушается
        RETURN i_id;
    END;

    INSERT INTO users_settings
        (user_id, radius, age_from, age_to, is_show_male, is_show_female, is_notification)
        VALUES (
            i_id,
            20,
            20,
            30,
            i_sex = 1, -- по умолчанию показываем мальчиков девочкам
            i_sex = 2, -- и девочек мальчикам
            TRUE
        );

    INSERT INTO users_index
        (user_id, sex, age)
        VALUES (
            i_id,
            i_sex,
            COALESCE(extract('year' from age(d_bdate)), 25)
        );

    INSERT INTO users_matches
        (user_id)
        VALUES (
            i_id
        );

    INSERT INTO users_profiles_vk
        (user_id)
        VALUES (
            i_id
        );

    INSERT INTO stats.users_overall
        SELECT  i_id;

    RETURN i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


