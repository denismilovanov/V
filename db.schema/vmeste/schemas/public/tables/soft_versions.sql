-------------------------------------------------------------
-- программы

CREATE TABLE public.soft_versions (
    -- 2.1.12  -> 20112
    id integer NOT NULL,
    device_type integer NOT NULL,
    created_at timestamp without time zone NOT NULL DEFAULT now(),
    is_actual boolean NOT NULL DEFAULT FALSE,
    CONSTRAINT soft_versions_pkey PRIMARY KEY (id, device_type)
);

ALTER TABLE public.soft_versions
    ADD COLUMN description text NULL;


