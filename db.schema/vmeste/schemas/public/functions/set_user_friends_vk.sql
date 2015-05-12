-------------------------------------------------------------
-- обновление списка друзей пользователя,
-- сразу целиком, без всяких моделек и циклов

CREATE OR REPLACE FUNCTION public.set_user_friends_vk(
    i_user_id integer,
    ai_friends_ids integer[]
)
RETURNS void AS
$BODY$
DECLARE
    b_changed boolean := FALSE;
BEGIN

    -- удаляем отсутствующих
    DELETE FROM users_friends_vk
        WHERE   user_id = i_user_id AND
                friend_id != ALL(ai_friends_ids);

    b_changed := FOUND;

    -- вставляем новых
    INSERT INTO users_friends_vk
        SELECT  i_user_id,
                friend_id
            FROM unnest(ai_friends_ids) AS friend_id
            WHERE friend_id NOT IN (
                SELECT friend_id
                    FROM users_friends_vk
                    WHERE user_id = i_user_id
            );

    b_changed := b_changed OR FOUND;

    -- обновляем поисковый индекс
    IF b_changed THEN
        UPDATE users_index
            SET friends_vk_ids = ai_friends_ids
            WHERE user_id = i_user_id;
    END IF;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


