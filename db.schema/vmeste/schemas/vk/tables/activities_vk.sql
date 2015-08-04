-------------------------------------------------------------
-- деятельность

CREATE TABLE vk.activities_vk (
    id integer NOT NULL DEFAULT nextval('vk.activities_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

