-------------------------------------------------------------
-- фотографии

CREATE TABLE public.users_photos (
    -- айди
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('users_photos_id_seq'::regclass),
    -- пользователь
    user_id integer NOT NULL REFERENCES public.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    -- откуда фотография?
    source_id integer NOT NULL REFERENCES photos_sources (id) DEFERRABLE INITIALLY DEFERRED,
    -- полный урл, если фотография внешняя
    url character varying(255) NULL,
    -- хеш, если фотография внутренняя
    hash character varying(40) NULL,
    extension character varying(3) NULL,
    --
    created_at timestamp with time zone DEFAULT now(),
    is_deleted boolean NOT NULL DEFAULT FALSE,
    is_deployed boolean NOT NULL DEFAULT FALSE
);

-- порядок
ALTER TABLE public.users_photos
    ADD COLUMN rank integer NOT NULL DEFAULT 0;

CREATE INDEX users_photos_user_id_idx
    ON public.users_photos
    USING btree(user_id);
