-------------------------------------------------------------
-- диалоги, на каждый диалог заводится пара записей

CREATE TABLE public.messages_dialogs (
    me_id integer NOT NULL REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    buddy_id integer NOT NULL REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    last_message text NOT NULL,
    last_message_i boolean NOT NULL,
    is_new boolean NOT NULL DEFAULT TRUE,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    CONSTRAINT messages_dialogs_pkey PRIMARY KEY (me_id, buddy_id)
);

ALTER TABLE public.messages_dialogs
    ADD COLUMN is_buddy_blocked boolean NOT NULL DEFAULT FALSE;

