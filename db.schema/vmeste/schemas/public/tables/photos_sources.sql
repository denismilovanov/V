-------------------------------------------------------------
-- источники фотографий

CREATE TABLE public.photos_sources (
    id integer PRIMARY KEY,
    name varchar NOT NULL UNIQUE,
    index varchar NOT NULL UNIQUE
);
