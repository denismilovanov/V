-------------------------------------------------------------
-- сообщения чата

CREATE TABLE public.messages (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('messages_id_seq'::regclass),
    from_user_id integer NOT NULL REFERENCES public.users(id),
    to_user_id integer NOT NULL REFERENCES public.users(id),
    created_at timestamp without time zone DEFAULT date_trunc('second'::text, now()) NOT NULL,
    message text
);

CREATE INDEX fk_messages_users1_idx
    ON public.messages
    USING btree (to_user_id);

CREATE INDEX fk_messages_users_idx
    ON public.messages
    USING btree (from_user_id);

CREATE INDEX messages_users1_users2_idx
    ON public.messages
    USING btree (from_user_id, to_user_id);
