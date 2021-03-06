-------------------------------------------------------------
-- устройства

CREATE TABLE public.users_devices (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('users_devices_id_seq'::regclass),
    user_id integer NOT NULL REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    key character varying(40) NOT NULL UNIQUE,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    device_token character varying(255),
    device_type integer NOT NULL
);

ALTER TABLE public.users_devices
    ADD COLUMN soft_version integer NULL;

-- alter table users_devices  alter COLUMN  device_type type integer using device_type::integer;

ALTER TABLE public.users_devices
    ADD CONSTRAINT users_devices_soft_version_fkey
    FOREIGN KEY (soft_version, device_type)
    REFERENCES public.soft_versions (id, device_type) DEFERRABLE INITIALLY DEFERRED;

-- констрейнт на длину токена
ALTER TABLE public.users_devices
    ADD CONSTRAINT token_length CHECK (
        CASE WHEN device_type = 1 AND device_token IS NOT NULL THEN length(device_token) = 64
             WHEN device_type = 2 AND device_token IS NOT NULL THEN length(device_token) BETWEEN 140 AND 4096
             ELSE TRUE
        END
    );

ALTER TABLE public.users_devices
    ALTER COLUMN key DROP NOT NULL;

ALTER TABLE public.users_devices
    ALTER COLUMN device_type SET NOT NULL;

CREATE UNIQUE INDEX users_devices_device_udx
    ON public.users_devices
    USING btree(user_id, device_type);
