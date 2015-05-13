-------------------------------------------------------------
-- сообщения, на каждое сообщение возникает пара симметричных записей

CREATE TABLE public.messages_new (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('messages_id_seq'::regclass),
    me_id integer NOT NULL REFERENCES public.users (id),
    buddy_id integer NOT NULL REFERENCES public.users (id),
    message text NOT NULL,
    i boolean NOT NULL, -- я написал или мне написали?
    is_new boolean NOT NULL DEFAULT TRUE,
    created_at timestamp without time zone DEFAULT now()
);

CREATE INDEX messages_new_pair_idx
    ON public.messages_new
    USING btree (me_id, buddy_id);
