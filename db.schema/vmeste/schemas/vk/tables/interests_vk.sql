-------------------------------------------------------------
-- интересы

CREATE TABLE vk.interests_vk (
    id integer NOT NULL DEFAULT nextval('vk.interests_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

