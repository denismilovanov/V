-------------------------------------------------------------
-- профиль ВК

CREATE TABLE public.users_info_vk (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users(id),
    vk_id character varying(45),
    token character varying(255),
    avatar_url character varying(255),
    sex integer,
    name character varying(255),
    bdate date
);

CREATE UNIQUE INDEX users_info_vk_udx
    ON public.users_info_vk
    USING btree(vk_id);

ALTER TABLE public.users_info_vk
    DROP COLUMN avatar_url;
