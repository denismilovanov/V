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
