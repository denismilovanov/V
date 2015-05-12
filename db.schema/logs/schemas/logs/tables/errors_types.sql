
CREATE TABLE logs.errors_types (
    id integer PRIMARY KEY,
    name varchar NOT NULL UNIQUE,
    index varchar NOT NULL UNIQUE
);
