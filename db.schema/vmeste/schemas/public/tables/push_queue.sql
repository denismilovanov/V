-------------------------------------------------------------
-- очередь на пуш

CREATE TABLE public.push_queue (
    id bigint NOT NULL DEFAULT nextval('push_queue_id_seq'::regclass) PRIMARY KEY,
    push_type_id integer NOT NULL,
    to_user_id integer NOT NULL REFERENCES public.users (id),
    from_user_id integer NOT NULL REFERENCES public.users (id),
    status_id integer NOT NULL DEFAULT 1,
    added_at timestamp with time zone NOT NULL DEFAULT now(),
    pushed_at timestamp with time zone NULL
);


