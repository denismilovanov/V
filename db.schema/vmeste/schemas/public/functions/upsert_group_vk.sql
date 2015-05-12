-------------------------------------------------------------
-- единственно правильный метод добавления или обновления сущности

CREATE OR REPLACE FUNCTION public.upsert_group_vk(
    i_id integer,
    s_name varchar,
    s_photo_url varchar
)
RETURNS bigint AS
$BODY$
DECLARE

BEGIN

    s_name := substring(s_name from 1 for 255);
    s_photo_url := substring(s_photo_url from 1 for 255);

    UPDATE groups_vk
        SET name = s_name,
            photo_url = s_photo_url
        WHERE id = i_id;

    IF NOT FOUND THEN

        INSERT INTO groups_vk
            (id, name, photo_url)
            VALUES (
                i_id,
                s_name,
                s_photo_url
            );

    END IF;

    RETURN i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


