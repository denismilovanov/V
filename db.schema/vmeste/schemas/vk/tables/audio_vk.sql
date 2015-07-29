-------------------------------------------------------------
-- треки

CREATE TABLE vk.audio_vk (
    id integer NOT NULL DEFAULT nextval('vk.audio_vk_id_seq'::regclass) PRIMARY KEY,
    artist_id integer NOT NULL,
    name varchar NOT NULL
);

CREATE UNIQUE INDEX
    ON vk.audio_vk
    USING btree(artist_id, vk.prepare(name));

