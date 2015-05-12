-------------------------------------------------------------
-- ?

CREATE TABLE requests_log (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('requests_log_id_seq'::regclass),
    user_id integer,
    created_at timestamp without time zone DEFAULT now(),
    execution_time double precision,
    status smallint,
    params json,
    method text
);
