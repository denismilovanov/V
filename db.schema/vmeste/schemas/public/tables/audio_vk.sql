-------------------------------------------------------------
-- треки

CREATE TABLE vk.audio_vk (
    id integer NOT NULL DEFAULT nextval('vk.audio_vk_id_seq'::regclass) PRIMARY KEY,
    artist_id integer NOT NULL,
    name varchar NOT NULL
);


