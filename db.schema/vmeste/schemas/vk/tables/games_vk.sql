-------------------------------------------------------------
-- игры

CREATE TABLE vk.games_vk (
    id integer NOT NULL DEFAULT nextval('vk.games_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

