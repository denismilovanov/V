-------------------------------------------------------------
-- исполнители

CREATE TABLE vk.audio_artists_vk (
    id integer NOT NULL DEFAULT nextval('vk.audio_artists_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

CREATE UNIQUE INDEX
    ON vk.audio_artists_vk
    USING btree(vk.prepare(name));
