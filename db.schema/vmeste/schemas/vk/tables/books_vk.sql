-------------------------------------------------------------
-- книжки

CREATE TABLE vk.books_vk (
    id integer NOT NULL DEFAULT nextval('vk.books_vk_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL
);

