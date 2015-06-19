
CREATE UNLOGGED TABLE logs.requests (
    id bigint PRIMARY KEY DEFAULT nextval('requests_id_seq'),
    request json NOT NULL,
    added_at timestamp with time zone NOT NULL DEFAULT now(),
    user_id integer NULL
);
