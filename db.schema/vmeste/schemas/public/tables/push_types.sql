
CREATE TABLE public.push_types (
    id integer PRIMARY KEY,
    name varchar NOT NULL UNIQUE,
    index varchar NOT NULL UNIQUE
);
