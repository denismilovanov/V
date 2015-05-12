-------------------------------------------------------------
-- ?

CREATE TABLE public.messages_last (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('messages_last_id_seq'::regclass),
    from_user_id integer NOT NULL REFERENCES public.users(id),
    to_user_id integer  NOT NULL REFERENCES public.users(id),
    updated_at timestamp without time zone DEFAULT now(),
    message character varying(255),
    is_new boolean,
    is_send boolean DEFAULT false
);

CREATE INDEX messages_last_idx_from
    ON public.messages_last
    USING btree (from_user_id);

CREATE INDEX messages_last_idx_to
    ON public.messages_last
    USING btree (to_user_id);
