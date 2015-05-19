-------------------------------------------------------------
-- пользователи

CREATE TABLE admin.users (
    id integer NOT NULL DEFAULT nextval('admin.users_id_seq'::regclass) PRIMARY KEY,
    name varchar NOT NULL,
    email varchar NOT NULL,
    password varchar(60) NOT NULL,
    remember_token varchar NULL,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone NOT NULL DEFAULT now()
);


