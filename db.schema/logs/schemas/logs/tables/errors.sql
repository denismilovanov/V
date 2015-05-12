
CREATE UNLOGGED TABLE logs.errors (
    id bigint PRIMARY KEY DEFAULT nextval('errors_id_seq'),
    type_id integer NOT NULL REFERENCES logs.errors_types DEFERRABLE INITIALLY DEFERRED,
    header text NOT NULL,
    message text NOT NULL,
    added_at timestamp with time zone NOT NULL DEFAULT now(),
    status_id integer NOT NULL DEFAULT 1
);
