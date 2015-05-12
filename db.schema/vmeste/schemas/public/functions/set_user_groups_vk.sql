-------------------------------------------------------------
-- обновление списка групп пользователя,
-- сразу целиком, без всяких моделек и циклов

CREATE OR REPLACE FUNCTION public.set_user_groups_vk(
    i_user_id integer,
    ai_groups_ids integer[]
)
RETURNS void AS
$BODY$
DECLARE
    b_changed boolean := FALSE;
BEGIN

    -- удаляем отсутствующих
    DELETE FROM users_groups_vk
        WHERE   user_id = i_user_id AND
                group_id != ALL(ai_groups_ids);

    b_changed := FOUND;

    -- вставляем новых
    INSERT INTO users_groups_vk
        SELECT  i_user_id,
                group_id
            FROM unnest(ai_groups_ids) AS group_id
            WHERE group_id NOT IN (
                SELECT group_id
                    FROM users_groups_vk
                    WHERE user_id = i_user_id
            );

    b_changed := b_changed OR FOUND;

    -- обновляем поисковый индекс
    IF b_changed THEN
        UPDATE users_index
            SET groups_vk_ids = ai_groups_ids
            WHERE user_id = i_user_id;
    END IF;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


