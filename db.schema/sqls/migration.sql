create extension postgres_fdw;
CREATE SERVER old FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host 'localhost', dbname 'vmeste', port '5432');
alter server old owner to vmeste;
CREATE USER MAPPING FOR vmeste SERVER old OPTIONS (user 'vmeste', password 'vmeste');

CREATE FOREIGN TABLE old_users_info_vk (
    user_id      integer                  not null,
    vk_id        character varying(45)    ,
    token        character varying(255)   ,
    avatar_url   character varying(255)   ,
    sex          integer                  ,
    name         character varying(255)   ,
    bdate        date
)
SERVER old OPTIONS(table_name 'users_info_vk');

CREATE FOREIGN TABLE  old_users_settings (
    user_id integer,
    radius integer,
    age_from integer,
    age_to integer,
    is_show_male boolean,
    is_show_female boolean,
    is_notification boolean,
    is_notification_likes boolean,
    is_notification_messages boolean
)SERVER old OPTIONS(table_name 'users_settings');


CREATE FOREIGN TABLE  old_users_groups_vk (
    user_id integer,
    group_id integer
)SERVER old OPTIONS(table_name 'users_groups_vk');

CREATE FOREIGN TABLE  old_users_friends_vk (
    user_id integer,
    friend_id integer
)SERVER old OPTIONS(table_name 'users_friends_vk');

CREATE FOREIGN TABLE  old_checkins (
    user_id      integer                       not null,
    latitude     double precision             ,
    longitude    double precision             ,
    created_at   timestamp without time zone  ,
    geo          geometry(Point)
)SERVER old OPTIONS(table_name 'checkins');


DO $$
DECLARE
    r_row public.users;
BEGIN

    FOR r_row IN SELECT
                        300000 + user_id,
                        now(),
                        sex,
                        name,
                        bdate,
                        '',
                        'f', 't', 'f',
                        avatar_url,
                        +3,
                        now(),
                        'f',
                        vk_id::integer
                    from old_users_info_vk
                    where vk_id is not null and vk_id <> ''
    LOOP

        RAISE NOTICE '%', r_row.vk_id;

        BEGIN
            INSERT INTO users
                SELECT r_row.*;
        EXCEPTION WHEN unique_violation THEN
        END;

    END LOOP;

END;$$;

 alter sequence users_id_seq restart with 350000;


insert into stats.users_overall select id from users;
insert into users_matches select id from users;
insert into users_profiles_vk select id from users;


update users set sex = 2 where sex not in (1,2);

insert into users_photos
    (user_id, source_id, url)
    select id, 1, avatar_url
        from users
        where avatar_url <> 'http://vk.com/images/camera_200.gif';


DO $$
DECLARE
    r_row public.users_settings;
BEGIN

    FOR r_row IN SELECT *
                    from old_users_settings
    LOOP

        r_row.user_id := r_row.user_id + 300000;

        RAISE NOTICE '%', r_row.user_id;

        BEGIN
            INSERT INTO users_settings
                SELECT r_row.*;
        EXCEPTION WHEN OTHERS THEN
        END ;


    END LOOP;

END;$$;

insert into users_settings
    select  id,
            30,
            20,
            30,
            sex = 1,
            sex = 2,
            't', 't', 't'
        from users
        where not exists (select 1 from users_settings where id = user_id);


insert into public.users_groups_vk
    select distinct user_id + 300000, group_id
        from old_users_groups_vk
        join users on id = user_id + 300000;

insert into users_index
    (user_id, sex, age)
    select id, sex, coalesce(extract('year' from age(bdate)), 25)
    from users;

update users_index as i
    set groups_vk_ids = coalesce(
        (select array_agg(group_id) from users_groups_vk g where g.user_id  = i.user_id),
        array[]::integer[]
    );


DO $$
DECLARE
    r_row record;
BEGIN

    FOR r_row IN SELECT *
                    from old_checkins
    LOOP

        PERFORM checkin(r_row.user_id + 300000, r_row.latitude, r_row.longitude, NULL, NULL)
            FROM users
            WHERE id = r_row.user_id + 300000;

    END LOOP;

END;$$;

update users_index  set last_updated_at = now() - interval '1 minute' * (random() * 500);


insert into likes
    select  nextval('likes_id_seq'::regclass),
            user1_id + 300000,
            user2_id + 300000,
            liked_at,
            is_liked,
            is_send,
            is_blocked,
            reason,
            is_new
        from old_likes
        where   exists (select 1 from users as u where u.id = user1_id + 300000) and
                exists (select 1 from users as u where u.id = user2_id + 300000);

insert into messages_new
    select  nextval('messages_id_seq'::regclass),
            from_user_id + 300000,
            to_user_id + 300000,
            message,
            't',
            'f',
            created_at
        from old_messages
        where   exists (select 1 from users as u where u.id = from_user_id + 300000) and
                exists (select 1 from users as u where u.id = to_user_id + 300000);


insert into messages_new
    select  nextval('messages_id_seq'::regclass),
            to_user_id + 300000,
            from_user_id + 300000,
            message,
            'f',
            'f',
            created_at
        from old_messages
        where   exists (select 1 from users as u where u.id = from_user_id + 300000) and
                exists (select 1 from users as u where u.id = to_user_id + 300000);


insert into messages_dialogs
    select  me_id,
            buddy_id,
            coalesce((select message from messages_new AS mn where mn.me_id = m.me_id and mn.buddy_id = m.buddy_id order by created_at desc limit 1), ''),
            'f',
            'f',
            coalesce((select min(created_at) from messages_new as mn where mn.me_id = m.me_id and mn.buddy_id = m.buddy_id), now()),
            coalesce((select max(created_at) from messages_new as mn where mn.me_id = m.me_id and mn.buddy_id = m.buddy_id), now()),
            exists (select 1 from likes as l where l.user1_id = m.me_id and l.user2_id = m.buddy_id and is_blocked)
        from messages_new as m
        group by me_id, buddy_id;
