-------------------------------------------------------------
-- фотографии ВК (пока что 1 на пользователя)

CREATE TABLE users_photo_vk (
    user_id integer NOT NULL REFERENCES users(id),
    url character varying(255)
);


