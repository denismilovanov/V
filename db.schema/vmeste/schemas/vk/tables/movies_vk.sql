-------------------------------------------------------------
-- фильмы

CREATE TABLE vk.movies_vk (
    id integer NOT NULL DEFAULT nextval('vk.movies_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

