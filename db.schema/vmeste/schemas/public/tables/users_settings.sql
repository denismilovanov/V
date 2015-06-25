-------------------------------------------------------------
-- настройки поиска

CREATE TABLE users_settings (
    user_id integer NOT NULL PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    radius integer,
    age_from integer,
    age_to integer,
    is_show_male boolean,
    is_show_female boolean,
    is_notification boolean,
    is_notification_likes boolean DEFAULT true,
    is_notification_messages boolean DEFAULT true
);
