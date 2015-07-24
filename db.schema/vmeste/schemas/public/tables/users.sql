-------------------------------------------------------------
-- пользователи

CREATE TABLE users (
    id integer NOT NULL DEFAULT nextval('users_id_seq'::regclass) PRIMARY KEY,
    updated_at timestamp without time zone,
    sex smallint,
    name character varying(255),
    bdate date,
    about character varying(500),
    is_deleted boolean DEFAULT false,
    is_moderated boolean DEFAULT false,
    is_blocked boolean DEFAULT false
);

CREATE INDEX users_bdate
    ON users
    USING btree (bdate);

CREATE INDEX users_idx_sex
    ON users
    USING btree (sex);

ALTER TABLE public.users
    ADD COLUMN avatar_url varchar(255) NOT NULL DEFAULT '';

ALTER TABLE public.users
    ADD COLUMN time_zone integer NOT NULL DEFAULT +3;

ALTER TABLE public.users
    ADD COLUMN registered_at timestamp with time zone NOT NULL DEFAULT now(),
    ADD COLUMN is_blocked_by_vk boolean NOT NULL DEFAULT FALSE;

ALTER TABLE public.users
    ADD COLUMN vk_id integer NOT NULL UNIQUE;

ALTER TABLE public.users
    ADD COLUMN params hstore NOT NULL DEFAULT '';

ALTER TABLE public.users
    ADD COLUMN vk_access_token varchar NULL;
