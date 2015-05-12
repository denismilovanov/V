-------------------------------------------------------------
-- устройства

CREATE TABLE public.users_devices (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('users_devices_id_seq'::regclass),
    user_id integer NOT NULL REFERENCES users(id),
    key character varying(40) NOT NULL UNIQUE,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    device_token character varying(255),
    device_type integer
);

ALTER TABLE public.users_devices
    ADD COLUMN soft_version integer NULL;

-- alter table users_devices  alter COLUMN  device_type type integer using device_type::integer;

ALTER TABLE public.users_devices
    ADD CONSTRAINT users_devices_soft_version_fkey
    FOREIGN KEY (soft_version, device_type)
    REFERENCES public.soft_versions (id, device_type) DEFERRABLE INITIALLY DEFERRED;

