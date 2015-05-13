create role vmeste_test with password '1gH&61_#:lJ' login;
create database logs_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database vmeste_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;


create database matches lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;

CREATE SERVER main_server FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host 'localhost', dbname 'vmeste', port '5432');

CREATE USER MAPPING FOR vmeste SERVER main_server OPTIONS (user 'vmeste', password 'vmeste');

CREATE FOREIGN TABLE public.users_index (
    user_id integer NOT NULL,
    geography geography NULL,
    sex integer NOT NULL,
    friends_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    groups_vk_ids integer[] NOT NULL DEFAULT array[]::integer[],
    likes_count integer NOT NULL DEFAULT 0,
    novelty_weight integer NOT NULL DEFAULT 0,
    age integer NOT NULL
)
SERVER main_server;
