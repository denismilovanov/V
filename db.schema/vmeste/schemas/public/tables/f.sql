-------------------------------------------------------------
-- ?

CREATE TABLE f (
    id integer NOT NULL PRIMARY KEY,
    user_id integer,
    vk_id character varying(45),
    sex smallint,
    created_at timestamp without time zone,
    added_at timestamp without time zone,
    name character varying(255),
    avatar_url character varying(255),
    latitude double precision,
    longitude double precision,
    geo geometry(Point),
    bdate date,
    is_new boolean DEFAULT true,
    city_id integer,
    deactivated character varying(15)
);

