-------------------------------------------------------------
-- музыка

CREATE TABLE vk.music_vk (
    id integer NOT NULL DEFAULT nextval('vk.music_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

